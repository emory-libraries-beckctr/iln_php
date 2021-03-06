<?php

/* usage: 
   search.php?region=a&term=b&region2=c&term2=d&sort=e&max=f&op=g
   for example:

search.php?region=article&term=lincoln&sort=date&op=and&region2=title&term2=america
 
(values are as specified below)  */

include_once("config.php");
include_once("lib/xmlDbConnection.class.php");
include_once("common_functions.php");

$region = $_GET["region"]; //options: document|article|title|date|illustration
$term =  $_GET["term"];  // search string for region above
$region2 = $_GET["region2"];  // same as first region
$term2 = $_GET["term2"];
$sort = $_GET["sort"]; // options: date|type|title
$maxdisplay = $_GET["max"];
$position = $_GET["pos"];  // position (i.e, cursor)
$operator = $_GET["op"];  // and|or

$args = array('host' => $tamino_server,
	      'db' => $tamino_db,
	      'coll' => $tamino_coll,
	      'debug' => false);
$xmldb = new xmlDbConnection($args);
$xsl    = "search.xsl";

// pass terms into xslt as parameters 
// (needed to pass along in link to browse page for highlighting)
$xsl_params = array("term"  => $term, "term2" => $term2);

// set a default maxdisplay
if ($maxdisplay == '') $maxdisplay = 20;
// if no position is specified, start at 1
if ($position == '') $position = 1;

$reg = getRegion($region);
if ($term2) { $reg2 = getRegion($region2); }

switch ($sort) {
 case "type"  : $_sort = "@type"; break;
 case "title" : $_sort = "head"; break;
 case "date"  : $_sort = "bibl/date/@value";  break;
 case "match" :
 default      : $_sort = "xs:int(count) descending";  break; 		// note: must type count to sorted as integer instead of string
}

// construct xquery
$declare ='declare namespace tf="http://namespaces.softwareag.com/tamino/TaminoFunction" 
declare namespace xs="http://www.w3.org/2001/XMLSchema" ';
$for = 'for $a in input()/TEI.2/:text/body/div1/div2';
$let = "let \$ref1 := tf:createTextReference(\$a, '$term')";
if ($term2) { 	// if there is a second search term, create text reference & include in total count
  $let .= " let \$ref2 := tf:createTextReference(\$a, '$term2') let \$ref := (\$ref1, \$ref2)";
} else { $let .= ' let $ref := ($ref1)'; }
  
// FIXME: handling second term?
$where  = "where tf:containsText(\$a$reg, '$term')";
if ($term2) { $where .= " $operator tf:containsText(\$a$reg2, '$term2')"; }
$return = 'return <div2>{$a/@id}{$a/@type}{$a/head}{$a/bibl}
  <count>{count($ref)}</count></div2>';
$qsort = "sortby ($_sort)"; 

$total = "<total>{count($for $where return \$a)}</total>";
//$xquery = "$declare <result>{$for $let $where $return $qsort} $total </result>";
$end = $position + $maxdisplay - 1;	// end of current segment: start + max - 1 (e.g., display 1 - 20)
$xquery = "$declare" . ' <result>{' . "($for $let $where $return $qsort)[position() >= $position and position() <= $end]" . '}' . "$total </result>";

html_head("Search Results");

include("xml/head.xml");
include("xml/sidebar.xml");

print '<div class="content"> 
          <h2>Search Results</h2>';


// run the query
$xmldb->xquery($xquery);	// position & maxdisplay handled within the query itself

print "<center><font size='+1'>";
if ($xmldb->count == 0) { print "No matches "; }
else if ($xmldb->count == 1) { print "$xmldb->count match "; }
else { print "$xmldb->count matches "; }
print "found for $begin_hi$term$end_hi in $region ";
if ($term2) { print "$op $begin_hi2$term2$end_hi in $region2"; }
print "</font><p>"; 


// store result links in a string to print it twice (top & bottom of page)
$result_links = '';

// if there are further pages of search results, link to them.
if ($xmldb->count > $maxdisplay) {
  $result_links .= '<li class="firsthoriz">More results:</li>';
  for ($i = 1; $i <= $xmldb->count; $i += $maxdisplay) {
    if ($i == 1) {
      $result_links .= '<li class="firsthoriz">';
    } else { 
      $result_links .= '<li class="horiz">';
    }
    // reconstruct the url and search terms
    $url = "search.php?region=$region&term=$term&max=$maxdisplay";
    if ($term2) {
      $url .= "&term2=$term2&region2=$region2&op=$operator";
    }
    if ($sort) {
      $url .= "&sort=$sort";
    }
    // now add the key piece: the new position
    $url .= "&pos=$i";
    if ($i != $position) {
      $result_links .= "<a href='$url'>";
      // url should be based on current search url, with new position defined
    }
    $j = min($xmldb->count, ($i + $maxdisplay - 1));
    // special case-- last set only has one result
    if ($i == $j) {
      $result_links .= "$i";
    } else {
      $result_links .= "$i - $j";
    }
    if ($i != $position) {
      $result_links .= "</a>";
    }
    $result_links .= "</li>";
  }
}

print "$result_links<p>";

// Don't display sort options if there are no results
if ($xmldb->count) {
  sort_options($sort);
}

print "</center>";

print "<hr>";

// transform xml
$xmldb->xslTransform($xsl, $xsl_params);
$myterms = array($term, $term2);
$xmldb->printResult($myterms);

print "<hr>\n";

print "<center>$result_links</center>\n";

print "</div>\n";
   
include("xml/foot.xml"); 

function getRegion ($r) {
   switch ($r) {
 case "article" : $myreg = "/p"; break;
 case "title"   : $myreg = "/head"; break;
 case "date"    : $myreg = "/bibl/date"; break;
   // case "illustration" : $reg = "@type='Illustration' and ."; break;
 case "illustration" : $myreg = "/p/figure/head"; break;
 case "document":   // same as default
 default:          $myreg = ""; break;
 }
   return $myreg;
}

function sort_options ($current) {
  // use the global variables
  global $region, $term, $region2, $term2, $operator, $maxdisplay, $position;
  $sort_url = "search.php?region=$region&term=$term&max=$maxdisplay&pos=$position";
  if ($term2) {
    $sort_url .= "&term2=$term2&region2=$region2&op=$operator";
  }
  
  $option = array("match" => "Relevance", 
		"date" => "Date", "type" => "Type", "title" => "Title");
  print "<li class='firsthoriz'>Currently sorting by <b>$option[$current]</b>. Re-sort by:</li>";
  $first_opt = "match";

  foreach ($option as $opt => $val) {
    if ($val == $option[$first_opt]) {
      print "<li class='firsthoriz'>";
    } else {
      print "<li class='horiz'>";
    }
    if ($opt == $current) {
      print "$val</li>";
    } else {
      print "<a href='$sort_url&sort=$opt'>$val</a></li>";
    }
  }
print "<p>";

}

?>


</body>
</html>
