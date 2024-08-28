<?php 
function get_vols() {
    return "SELECT * FROM volume WHERE 1 ORDER BY number ASC";
}
function get_issues($vol) {
    return "SELECT * FROM issue WHERE vol=" . $vol . " ORDER BY number ASC";
}
function get_issue($vol, $no) {
    return "SELECT * FROM issue WHERE vol=" . $vol . " AND number=" . $no;
}
function get_posts($vol, $issue) {
    return "SELECT * FROM post WHERE vol=" . $vol . " AND issue=" . $issue . " ORDER BY number ASC";
}
function get_post($vol, $issue, $no) {
    return "SELECT * FROM post WHERE vol=" . $vol . " AND issue=" . $issue . " AND number=" . $no;
}
function search($query, $sort) {
    return "SELECT vol, issue, number, date, author, alt_author, subject, content, SUBSTRING(content,GREATEST(1, LOCATE('" . $query . "', content) - 20),150) as excerpt FROM post WHERE MATCH (subject, content) AGAINST ('" . $query . "' IN BOOLEAN MODE) ORDER BY date " . $sort;
}
function get_by_author($query, $sort) {
    return "SELECT vol, issue, number, date, author, alt_author, subject, SUBSTRING(content,1,150) as excerpt FROM post WHERE author LIKE '%" . $query . "%' OR alt_author LIKE '%" . $query . "%' ORDER BY date " . $sort;
}
?>