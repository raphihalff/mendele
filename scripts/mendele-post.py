#!/usr/bin/env python
# coding: utf-8

import re
import dateutil.parser as dparser
from calendar import month_name
from os import walk
from os import listdir
import pickle
import datetime
import mysql.connector
from alive_progress import alive_bar
import argparse
from dateutil.tz import gettz

vols = []
issues = []
#format of pathname for future pickled directories
pickles = "something/pickled_vols/mendele_vol-{v}.pkl"

# translate the various mendele date formats to one unified format
# WILL make a few mistakes
def get_date(d, issue, default):
    tzinfos = {
      "CDT": -18000,
      "IST": +7200,
      "MST": -25200,
      "CST": -21600,
      "MDT": -21600,
      "PDT": -25200,
      "PST": -28800,
      "JST": +32400,
      "BST": +3600,
      "MEZ": +3600,
      "IDT": +10800,
      "MET": gettz("Europe/Amsterdam"),
      "CET": +3600,
      "ADT": -10800,
      "EET": +7200,
      "ARG": -10800,
      "U": gettz("UTC")
    }

    nd = "NULL"
    if not issue:
        d = d.replace("-DST", "")
        d = d.replace("DST", "")
        d = d.replace("20100", "2010")
        # d = d.replace("", "")
        d = d.replace("5EDT", "") #specific case
        if "=" in d:
            d = d[d.index("="):]
        d = re.sub("(?<=[+-])\s(?=[0-9]+)", "", d) #removes space between +/- and offset
        d = re.sub("[+-][0-9]{4}[0-9]+", "", d) #removes offset altogether if it is longer than 4 digits
        # d = re.sub("\s[0-9]{4}$", "", d)#remove offset without +/-
        years = re.findall("(?<![-+]{1})[0-9]{4}", d) #remove offset without +/-
        for year in years:
            if int(year) < 1991 or int(year) > 2017:
                d = d.replace(year, "")
        # d = re.sub("\([.*]\)", "", d)#remove offset without +/-
        d = re.sub("\(.{4}.+\)", "", d) #remove any parenthetical longer than 4 chars
        d = re.sub("[+-]{2}[0-9]{2}[0-9]+", "", d) #remove offset if preceded by both +-
        bad_offset = re.search("[-+]{1}[0-9]{3}(?:\s+|$)", d) #adds initial 0 if only 3 digit offset provided
        if bad_offset:
            d = d[:bad_offset.span()[0]+1] + "0" + d[bad_offset.span()[0]+1:]
        d = re.sub("(Subject)|(From).*$", "", d)
    if issue:
        d = d.replace("20011", "2011")
    if re.search("[0-9]+", d):
        nd = dparser.parse(d,fuzzy=True, default=default, tzinfos=tzinfos)
        # for mysql: YYYY-MM-DD HH:MI:SS
    return nd

# read txt archives, make lists or all issues and save as pickled file per vol
def get_issue(i):
    f = open(i, "r", encoding="latin-1")
    # if "vol22009" in i:
    #     for l in f:
    #         print(l)
    #     return 1
    global issues

    issue = {
        "date":"",
        "toc":"",
        "posts": [],
        "special": ""
    }
    posts = []
    post = {
        "date":"",
        "author":"",
        "subject":"",
        "content":""
    }

    p = -1
    contents = ""
    meta = -1
    sub_auth = []
    notes = ""

    iss_special = ""
    iss_date = ""
    iss_toc = ""
    end_toc = False
    for l in f:
        l = l.strip()
        # print(l)
        if (not end_toc) and ("Contents" in l):
            # the end of this line contains Vol and No, if you want it
            while not iss_date:
                iss_date = next(f).strip()
            # get toc
            toc_line = next(f).strip()
            while not re.search("^[0-9]\)-+", toc_line):
                if toc_line and not re.search("^[0-9]\) ", toc_line): #if not toc entries, this is the title of a spceial issue
                    iss_special += toc_line + "\n"
                elif toc_line and re.search("^[0-9]\) ", toc_line): #toc entries
                    if "(" in toc_line:
                        sub_auth.append((toc_line[3:toc_line.index("(")-1].strip(), toc_line[toc_line.index("(") + 1:-1].strip()))
                    else:
                        sub_auth.append((toc_line, "N/A"))
                    iss_toc += toc_line + "\n"
                toc_line = next(f).strip()
                l = toc_line
            end_toc = True
        if re.search("^[0-9]\)-{30}-+$", l) or re.search("^_{30}_+$", l) or "End of Mendele" in l: #start of post
            # prep for new post
            if not end_toc:
                continue;

            if p > -1:
                post["content"] = notes + contents
                posts.append(post)

            if re.search("^_{30}_+$", l) or "End of Mendele" in l:
                break

            p = int(l[:1]) - 1
            contents = ""
            notes = ""
            meta = 0
            post = {
                "date":"",
                "author":"",
                "subject":"",
                "content":""
            }
        elif meta > -1:
            if meta == 0:
                # check for Date, From, and Subject, favor from and subject here over sub_auth
                d_find = "Date:"
                f_find = "From:"
                s_find = "Subject:"
                while not l:
                    l = next(f).strip()

                if l[0] == "[":
                    while True:
                        if l[-1:] == "-":
                            notes += l[:-1]
                        else:
                            notes += l + " "
                        if "]" in l:
                            notes += "\n\n"
                            break
                        else:
                            l = next(f).strip()
                    l = next(f).strip()
                while not l:
                    l = next(f).strip()
                while True:
                    # print(l)
                    if d_find in l:
                        # print(l)
                        d = get_date(l[l.index(d_find)+len(d_find):].strip(), False, get_date(iss_date, True, datetime.datetime(9996, 12, 31)))
                        # print(d)
                        if d != "NULL":
                            d = d.strftime('%Y-%m-%d %H:%M:%S')
                        post["date"] = d
                        # dates.write(post["date"] + "\n")
                    elif f_find in l:
                        post["author"] = l[l.index(f_find)+len(f_find):].strip()
                    elif s_find in l:
                        post["subject"] = l[l.index(s_find)+len(s_find):].strip()
                    else:
                        break
                    l = next(f).strip()
                if not post["author"] and len(sub_auth) > p:
                    post["author"]= sub_auth[p][1]
                if not post["subject"] and len(sub_auth) > p:
                    post["subject"] = sub_auth[p][0]
                # overide "From:" author with TOC author for alt_auth column
                if len(sub_auth) > p:
                    post["author"]= sub_auth[p][1]
                meta = 1
            else:
                # check if empty, then add new line
                if not l:
                    contents += "\n\n"
                else:
                    if l[-1:] == "-":
                        contents += l[:-1]
                    else:
                        contents += l + " "

    f.close()
    # print("ISSUE"+iss_date+"/ISSUE")
    issue["date"] = get_date(iss_date, True, datetime.datetime(9996, 12, 31)).strftime('%Y-%m-%d')
    issue["toc"] = iss_toc
    issue["special"] = iss_special
    issue["posts"] = posts
    issues.append(issue)
    #clear

    issue = {
        "date":"",
        "toc":"",
        "posts": [],
        "special": ""
        }
    posts = []
    post = {
        "date":"",
        "author":"",
        "subject":"",
        "content":""
    }

def auth_mysql():
    # make connection, format query
    config = {
         'raise_on_warnings': True,
         'host': '',
         'password': '',
         'user': '',
         'database': ''
    }

    cnx = mysql.connector.connect(**config)
    return cnx

# iterate through pickled volumes and save insert to mysql
# each vol =
#  [issues{date, toc, posts[{date, author, subject, content}], special}]
# vol # and date range iter starting vol 1, 1991-92
# vol also takes # of issues
def post_msql(cnx, vol):
    # when adding to sql:
        # post date formatted to: YYYY-MM-DD
        # issue date formatted to: YYYY-MM-DD
        # vol date formatted to: YYYY
    # add a "note on timestamps" and a link to the txt.
    add_vol = ("INSERT INTO volume "
               "(number, date, issues) "
               "VALUES (%(number)s, %(date)s, %(issues)s)")
    add_iss = ("INSERT INTO issue "
               "(vol, number, date, toc, posts, special) "
               "VALUES (%(v)s, %(n)s, %(d)s, %(toc)s, %(p)s, %(s)s)")
    add_post = ("INSERT INTO post "
               "(vol, issue, number, date, author, subject, content) "
               "VALUES (%(v)s, %(i)s, %(n)s, %(d)s, %(a)s, %(s)s, %(c)s)")
    add_alt = ("UPDATE `post` SET `alt_author`=%(a)s WHERE `vol`=%(v)s and `issue`=%(i)s and `number`=%(n)s")
    init_date = 1990
    cursor = cnx.cursor()

    # start volume
    data_vol = {
        "number": vol[1],
        "date": init_date + vol[1],
        "issues": len(vol[0])
    }
    vol = vol[0]

    #cursor.execute(add_vol, data_vol)

    add_iss = ("INSERT INTO issue "
               "(vol, number, date, toc, posts, special) "
               "VALUES (%(v)s, %(n)s, %(d)s, %(toc)s, %(p)s, %(s)s)")
    issue_no = 1
    with alive_bar(len(vol), bar = 'bubbles', spinner = 'notes2') as bar:
        bar.text("Uploading to MySQL!")
        for issue in vol:
            # [issues{date, toc, posts[], special}]
            if data_vol["number"] == 5 and issue_no == 96:
                issue_no += 1
            elif data_vol["number"] == 15 and issue_no == 46:
                issue_no += 2
            bar.title("Vol. " + str(data_vol["number"]) + ", no. " + str(issue_no))
            data_iss = {
                "v": data_vol["number"],
                "n": issue_no,
                "d": issue["date"],
                "toc": issue["toc"],
                "p": len(issue["posts"]),
                "s": issue["special"]
            }
            #cursor.execute(add_iss, data_iss)
            post_no = 1
            for post in issue["posts"]:
                # (%(v)s, %(i)s, %(n)s, %(d)s, %(a)s, %(s)s, %(c)s)
                # {date, author, subject, content}
                data_post = {
                    "v": data_vol["number"],
                    "i": issue_no,
                    "n": post_no,
                    "d": post["date"],
                    "a": post["author"],
                    "s": post["subject"],
                    "c": post["content"]
                }
                #cursor.execute(add_post, data_post)
                cursor.execute(add_alt, data_post)

                post_no += 1
            issue_no += 1
            bar()

    cnx.commit()
    cursor.close()
    cnx.close()

def parse_txt_archive():
    # get volume dirs, sort, enter, get issues, sort, read, get toc, get posts, structure, add post, add to issue, add to vol
    # root of volume directories
    archive = ""
    global issues
    # get volume dirs, sort, enter, get issues, sort, read, get toc, get posts, structure, add post, add to issue, add to vol

    ds = ".DS_Store"
    vols_iter = sorted(listdir(archive))
    if ds in vols_iter:
        vols_iter.remove(ds)
    vol_i = 1
    with alive_bar(len(vols_iter), bar = 'bubbles', spinner = 'notes2') as bar:
        for vol_iter in vols_iter:
            bar.title("Parsing Vol. " + str(vol_i))
            db = open(pickles.format(v = vol_i), "wb")
            vol_i += 1
            issues_iter = sorted(listdir(archive + vol_iter))
            if ds in issues_iter:
                issues_iter.remove(".DS_Store")
            for iss_iter in issues_iter:
                # print(iss_iter)
                get_issue(archive + vol_iter + "/" + iss_iter)
            pickle.dump(issues, db)
            db.close()
            issues = []
            bar()

def get_vol(vol):
    f = open(pickles.format(v=vol), "rb")
    return (pickle.load(f), vol)
    # get specific vol

def main():
    parser = argparse.ArgumentParser("Mendele")
    parser.add_argument("vol", help="Volume number (1-26), from which to start uploading to db.", type=int)
    parser.add_argument('--reparse', action='store_true', help="Pass if the archive txt files should be reparsed before adding items to MySQL.")
    parser.set_defaults(reparse=False)
    args = parser.parse_args()
    if args.reparse:
        parse_txt_archive()
    if args.vol > 0:
        for i in range(args.vol, 27):
            post_msql(auth_mysql(), get_vol(i))

if __name__ == "__main__":
    main()
