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
    	$author = $_GET["author"];
    	$vol = 1;
    	$no = 1;
    	if (empty($author)) $author = "Josh Price";
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
    
    <div id="results">
       <h2>Results for <span class="highlight"><?php echo htmlspecialchars($author); ?></span></h2> 
       <div id="author-nav">
           <button id="sort" type="button" onclick="sort()">Newest First?</button><span class="hide">|</span>
            <form id="author-form" action="author.php" method="get">
                <input id="author-var-box" type="search" name="author" value="Or search by a variation..." onfocus="this.value=''">
                <input id="author-var-button" type="submit" value="Go Mendele, go!">
            </form>
       </div>
       
       <div id="oldest" style="display: block;">
           <?php 
                $results = $mysql->query(get_by_author($author, ""));
        		if ($results->num_rows > 0) {
        			while($result = $results->fetch_assoc()) {
        			    echo '<div class="post">';
                        echo '<h2 class="post-subject"><a class="post-result" href="https://mendele.yiddish.nu/index.php?vol=' . $result['vol'] . '&no=' . $result['issue'] . '#post' . $result["number"] . '">' . $result['subject'] . '</a></h2>';
                        echo '<h3 class="post-author"><form action="author.php" method="get"><button type="submit" class="author-link" name="author" value="' .  htmlspecialchars(empty($result['alt_author']) ? $result['author'] : $result['alt_author']) . '">From: <span class="author-highlight">' . htmlspecialchars(empty($result['alt_author']) ? $result['author'] : $result['alt_author']) . '</span></button></form></h3>';
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
                $results = $mysql->query(get_by_author($author, "DESC"));
        		if ($results->num_rows > 0) {
        			while($result = $results->fetch_assoc()) {
        			    echo '<div class="post" id="post' . $result['number'] . '">';
                        echo '<h2 class="post-subject"><a class="post-result" href="https://mendele.yiddish.nu/index.php?vol=' . $result['vol'] . '&no=' . $result['issue'] . '#post' . $result["number"] . '">' . $result['subject'] . '</a></h2>';
                        echo '<h3 class="post-author"><form action="author.php" method="get"><button type="submit" class="author-link" name="author" value="' . htmlspecialchars(empty($result['alt_author']) ? $result['author'] : $result['alt_author']) . '">From: <span class="author-highlight">' . htmlspecialchars(empty($result['alt_author']) ? $result['author'] : $result['alt_author']) . '</span></button></form></h3>';
                        echo '<h3 class="post-date">Sent on: ' . date_format(date_create($result['date']),"m/d/Y H:i:s") . '</h3>';
        		        echo '<div class="post-content">...' . str_replace("\n", "<br>", htmlspecialchars($result['excerpt'])) . '...</div>';
        		        echo '</div>';

        			}
        		} else {
        		    echo '<h2>NO RESULTS</h2>';
        		}
            ?>          
        </div>
    </div>
    <script>
        function showIssues(vol_form) {
            const issues = document.getElementsByClassName("issues");
            for (let i = 0; i < issues.length; i++) {
               issues.item(i).style.display = "none";
            }
            const issue_select = document.getElementsByClassName("issue_select");
            for (let i = 0; i < issue_select.length; i++) {
               issue_select.item(i).setAttribute("disabled", true);;
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

