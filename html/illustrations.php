<?php


include_once("link_admin/taminoConnection.class.php");
include("common_functions.php");


$args = array('host' => $tamino_server,
	      'db' => $tamino_db,
	      'coll' => $tamino_coll,
	      'debug' => false);
$tamino = new taminoConnection($args);

$query = 'for $b in input()/TEI.2/:text/body/div1
sort by (@id)
let $fig := $b//figure
return <div1 type="{$b/@type}">
 {$b/head}
 {$b/docDate}
 {$fig}
</div1>';
$xsl_file = "contents.xsl";
$xsl_params = array('mode' => "figure");
$rval = $tamino->xquery($query);
if ($rval) {       // tamino Error code (0 = success)
  print "<p>Error: failed to retrieve illustrations.<br>";
  print "(Tamino error code $rval)</p>";
  exit();
} 


html_head("Browse - Illustrations", true);

include("xml/head.xml");
include("xml/sidebar.xml");

print '<div class="content"> 
      <h2>Illustrations</h2>';


$tamino->xslTransform($xsl_file, $xsl_params);
$tamino->printResult();

print '</div>';
   
include("xml/foot.xml");

print '</body>
</html>';
