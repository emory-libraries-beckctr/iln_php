<?php

include_once("config.php");
include_once("lib/xmlDbConnection.class.php");
include("common_functions.php");
$exist_args{"debug"} =false;
$xmldb = new xmlDbConnection($exist_args);


$query = 'for $vol in //div1
order by $vol/@id
return <div1 type="{$vol/@type}"> 
{$vol/head} {$vol/docDate}
{for $art in $vol//div2 return 
<div2 id="{$art/@id}" type="{$art/@type}"> 
  {$art/head}{$art/bibl} 
  {for $fig in $art//figure return $fig} 
</div2>} 
</div1>';
/*
$tamino_query = 'for $b in input()/TEI.2//div1
return <div1>
 {$b/@type}
 {$b/head}
 {$b/docDate}
 { for $c in $b/div2 return
   <div2 id="{$c/@id}" type="{$c/@type}" n="{$c/@n}">
     {$c/head}
     {$c/bibl}
     {for $d in $c/p/figure return $d}
   </div2>
}
 </div1>';*/
/*
added this to query to test taminoConnection class
<total>{count(input()/TEI.2//div1/div2)}</total>
*/


$xmldb->xquery($query);

html_head("Browse", true);

include("web/xml/head.xml");
include("web/xml/sidebar.xml");

print '<div class="content"> 
          <h2>Browse</h2>';
$xsl_file = "xslt/contents.xsl";
$xmldb->xslTransform($xsl_file);
$xmldb->printResult();

print "<hr>";
print "</div>";
   
include("xml/foot.xml");

?>

</body>
</html>
