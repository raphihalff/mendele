<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="libs/jquery/1.11.1/jquery.min.js"></script>
    <link rel="stylesheet" type="text/css" href="style.css">
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <title>mendele</title>
    <div itemscope itemtype="https://schema.org/WebSite">
      <meta itemprop="url" content="https://mendele.yiddish.nu/"/>
      <meta itemprop="name" content="מענדעלע"/>
      <meta itemprop="alternateName" content="Mendele"/>
    </div>
</head>

<body onload="showFirstIssue()">
   <?php
        include_once $_SERVER['DOCUMENT_ROOT'].'/header.php';
        #include $_SERVER['DOCUMENT_ROOT'].'/nav.html';
    	require_once '/home/xn7dbl5/config/mysql_mendele_config.php';
    	require_once $_SERVER['DOCUMENT_ROOT'] . '/mendele_queries.php';			
    	
    	// Create connection
    	$mysql = new mysqli($servername, $username, $password, $dbname);
    	$mysql->set_charset('utf8');
    	// Check connection
    	if ($mysql->connect_error) {
    		die("Connection failed: " . $mysql->connect_error);
    	}
    	
    	$vol = $_GET["vol"];
    	$no = $_GET["no"];
    	$search = $_GET["search"];
    	if (empty($vol)) $vol = 1;
    	if (empty($no)) $no = 1;
    ?> 
    <div id="vol-browser">
        <form action="index.php" method="get">
            <label for="vol">Volume:</label>
            <select name="vol" id="vol" onchange="showIssues(this)">
            <?php 
                $volumes = $mysql->query(get_vols());
        		if ($volumes->num_rows > 0) {
        			while($volume = $volumes->fetch_assoc()) {
                        echo '<option value="' . $volume["number"] . '">Vol. ' . $volume["number"] . ' ('. $volume["date"] . ')</option>';
        			}
        		}
            ?>
            </select>
    
            <?php
                $volumes = $mysql->query(get_vols());
        		if ($volumes->num_rows > 0) {
        			while($volume = $volumes->fetch_assoc()) {
        			    echo '<div class="issues" id="' . $volume["number"] . 'issuesdiv">';
        			    echo '<label for="' . $volume["number"] . 'issues">Issue: </label>';
                        echo '<select name="no" id="' . $volume["number"] . 'issues" class="issue_select" onchange="this.form.submit()" disabled>';
                        $issues = $mysql->query(get_issues($volume["number"]));
                        if ($issues->num_rows > 0) {
                	        while($v_issue = $issues->fetch_assoc()) {
                                echo '<option value="' . $v_issue["number"]  . '">No. ' . $v_issue["number"] . ' (' . date_format(date_create($v_issue['date']),"M d") . ')</option>';
                	        }
                        }
                        echo '</select>';
                        echo '</div>';
        			}
        		}
            ?>
            <span class="hide">|</span>
            <div id="search">
                <input id="search-box" type="search" name="search" value="Search" onfocus="this.value=''">
                <input id="search-button" type="submit" value="Go Mendele, go!">
            </div>
        </form>
        <span class="hide">|</span> <a class="menu-item" href="library.html">Library</a> <span class="hide">|</span> <a class="menu-item" href="about.html">About Us</a>
    </div>
    <?php if (empty($search) || $search == "Search") { ?>
        <div id="issue">    
            <?php
                $issue = $mysql->query(get_issue($vol, $no));
                if ($issue->num_rows > 0) {
                    $issue = $issue->fetch_assoc();   
                }
            ?>
            <h2 id="issue-header">Mendele Vol. <?php echo $vol; ?>, No. <?php echo $no; ?></h2>
            <h3 id="issue-date"><?php echo date_format(date_create($issue['date']),"M d, Y"); ?></h3>
            <?php
            if (!empty($issue['special'])) {
                echo '<h3 id="issue-special">' . $issue['special'] . '</h3>';
            }
            ?>
            <div id="toc">
                <!--for each post, set post-->
                <?php
                    $toc = explode("\n", $issue['toc']);
                    $index = 1;
                    foreach ($toc as $line) {
                        echo '<h4 class="toc-entry"><a href="#post' . $index . '">' . $line . '</a></h4>'; 
                        $index++;
                    }
                ?>
            </div>
            <div class="nav">
                <a href="" class="prev">previous issue</a><span class="hide">|</span><a href="<?php echo 'files/archive/vol' . sprintf('%02s', $vol) . sprintf('%03s', $no) . '.txt'  ?>" download>download issue</a><span class="hide">|</span><a href="" class="next">next issue</a>
            </div>
            <?php
                $posts = $mysql->query(get_posts($vol, $no));
        		if ($posts->num_rows > 0) {
        			while($post = $posts->fetch_assoc()) {
        		      //  number, date, author, subject, content
        		      echo '<div class="post" id="post' . $post['number'] . '">';
        		      echo '<h2 class="post-subject">' . $post['number'] . ') ' . $post['subject'] . '</h2>';
        		      echo '<h3 class="post-author"><form action="author.php" method="get"><button type="submit" class="author-link" name="author" value="' . htmlspecialchars(empty($post['alt_author']) ? $post['author'] : $post['alt_author']) . '">From: <span class="author-highlight">' . htmlspecialchars(empty($post['alt_author']) ? $post['author'] : $post['alt_author']) . '</span></button></form></h3>';
        		      echo '<h3 class="post-date">Sent on: ' . date_format(date_create($post['date']),"m/d/Y H:i:s") . '</h3>';
                      echo '<div class="post-content">' . str_replace("\n", "<br>", htmlspecialchars($post['content'])) . '</div>';
        		      echo '</div>';
        		      echo '<hr>';
        			}
        		}
    	    ?>
    	    <div class="nav">
                <a href="" class="prev">previous issue</a><span class="hide">|</span><a href="<?php echo 'mendele_files/vol' . sprintf('%02s', $vol) . sprintf('%03s', $no) . '.txt'  ?>" download>download issue</a><span class="hide">|</span><a href="" class="next">next issue</a>
            </div>
        </div>
    <?php } else { ?>
        <div id="results">
           <h2>Results for <span class="highlight"><?php echo $search; ?></span></h2> 
           <div id="sort-nav">
                <button id="sort" type="button" onclick="sort()">Newest First?</button>
           </div>
           <div id="oldest" style="display: block;">
               <?php 
                    $results = $mysql->query(search($search, ""));
            		if ($results->num_rows > 0) {
            			while($result = $results->fetch_assoc()) {
            			    echo '<div class="post">';
                            echo '<h2 class="post-subject"><a class="post-result" href="https://mendele.yiddish.nu/index.php?vol=' . $result['vol'] . '&no=' . $result['issue'] . '#post' . $result["number"] . '">' . $result['subject'] . '</a></h2>';
                            echo '<h3 class="post-author"><form action="author.php" method="get"><button type="submit" class="author-link" name="author" value="' . htmlspecialchars(empty($result['alt_author']) ? $result['author'] : $result['alt_author']) . '">From: <span class="author-highlight">' . htmlspecialchars(empty($result['alt_author']) ? $result['author'] : $result['alt_author']) . '</span></button></form></h3>';
                            echo '<h3 class="post-date">Sent on: ' . date_format(date_create($result['date']),"m/d/Y H:i:s") . '</h3>';
            		        echo '<div class="post-content">...' . str_replace("\n", "<br>", htmlspecialchars($result['excerpt'])) . '...</div>';
            		        echo '</div>';
            		        echo '<hr>';
            			}
            		} else {
            		    echo '<h2>NO RESULTS</h2>';
            		}
                ?>
           </div>
           <div id="newest" style="display: none;">
               <?php 
                    $results = $mysql->query(search($search, "DESC"));
            		if ($results->num_rows > 0) {
            			while($result = $results->fetch_assoc()) {
            			    echo '<div class="post" id="post' . $result['number'] . '">';
                            echo '<h2 class="post-subject"><a class="post-result" href="https://mendele.yiddish.nu/index.php?vol=' . $result['vol'] . '&no=' . $result['issue'] . '#post' . $result["number"] . '">' . $result['subject'] . '</a></h2>';
                            echo '<h3 class="post-author"><form action="author.php" method="get"><button type="submit" class="author-link" name="author" value="' . htmlspecialchars(empty($result['alt_author']) ? $result['author'] : $result['alt_author']) . '">From: <span class="author-highlight">' . htmlspecialchars(empty($result['alt_author']) ? $result['author'] : $result['alt_author']) . '</span></button></form></h3>';
                            echo '<h3 class="post-date">Sent on: ' . date_format(date_create($result['date']),"m/d/Y H:i:s") . '</h3>';
            		        echo '<div class="post-content">...' . str_replace("\n", "<br>", htmlspecialchars($result['excerpt'])) . '...</div>';
            		        echo '</div>';
                            echo '<hr>';
            			}
            		} else {
            		    echo '<h2>NO RESULTS</h2>';
            		}
                ?>          
            </div>


        </div>
    <?php } ?>
    <script>
        function showIssues(vol_form) {
            const issues = document.getElementsByClassName("issues");
            for (let i = 0; i < issues.length; i++) {
               issues.item(i).style.display = "none";
            }
            const issue_select = document.getElementsByClassName("issue_select");
            for (let i = 0; i < issue_select.length; i++) {
               issue_select.item(i).setAttribute("disabled", true);
            }
            var v = document.getElementById("vol").value;
            document.getElementById(v + "issuesdiv").style.display = "inline";
            document.getElementById(v + "issues").removeAttribute("disabled");
            document.getElementById("issue").style.display = "none";
            vol_form.form.submit();
        }
        function showFirstIssue() {
            document.getElementById("vol").value = <?php echo $vol; ?>;
            document.getElementById("<?php echo $vol; ?>issuesdiv").style.display = "inline";
            document.getElementById("<?php echo $vol; ?>issues").removeAttribute("disabled");
            document.getElementById("<?php echo $vol; ?>issues").value = <?php echo $no; ?>;
           
            let max_vols = document.getElementById("vol").length;
            let max_issues = document.getElementById("<?php echo $vol; ?>issues").length;
            let next_buttons = document.getElementsByClassName("next");
            let prev_buttons = document.getElementsByClassName("prev");
            let cur_vol = parseInt(<?php echo $vol; ?>);
            let cur_no = parseInt(<?php echo $no; ?>);
            let next_vol = 0;
            let next_no = 0;
            let prev_vol = 0;
            let prev_no = 0;
            if (cur_no < max_issues) {
                next_no = cur_no + 1;
                next_vol = cur_vol;
            } else if (cur_vol < max_vols) {
                next_no = 1;
                next_vol = cur_vol + 1;
            } 
            if (cur_no > 1) {
                prev_no = cur_no - 1;
                prev_vol = cur_vol;
            } else if (cur_vol > 1) {
                prev_vol = cur_vol - 1;
                prev_no = document.getElementById(String(prev_vol).concat("issues")).length;
            } 
            
            for (let i = 0; i < next_buttons.length; i++) {
                if (next_no == 0) {
                    next_buttons[i].setAttribute("disabled", true);
                } else {
                    next_buttons[i].href = "index.php?vol=".concat(next_vol, "&no=", next_no);
                }
            }
            for (let i = 0; i < prev_buttons.length; i++) {
                if (prev_no == 0) {
                    prev_buttons[i].setAttribute("disabled", true);
                } else {
                    prev_buttons[i].href = "index.php?vol=".concat(prev_vol, "&no=", prev_no);
                }
            }
        }
        function sort(){
            var newest = document.getElementById("newest");
            var oldest = document.getElementById("oldest");
            var sort_button = document.getElementById("sort");
            if (newest.style.display === "none") {
                oldest.style.display = "none";
                newest.style.display = "block";
                sort_button.innerHTML = "Oldest First?"
            } else {
                oldest.style.display = "block";
                newest.style.display = "none";
                sort_button.innerHTML = "Newest First?"          
            }
        }
        
    </script>
</body>
</html>

