<?php

include_once("taminoConnection.class.php");
include_once("common_funcs.php");
include_once("subjectList.class.php");
include_once("phpDOM/classes/include.php");
import("org.active-link.xml.XML");

class linkRecord {

  var $tamino;

  var $id;
  var $title;
  var $url;
  var $subject;  
  var $description;
  var $contributor;
  var $ed_contributor;		// person contributing an edit
  var $date;
  var $lastModified;
  var $edit;
  var $all_subjects;
  /* original date / date last modified ? */


  // Constructor 
  function linkRecord($argArray, $subjArray = NULL) {
    // pass host/db/collection settings to tamino object
    $this->tamino = new taminoConnection($argArray);

    $this->id = $argArray['id'];
    $this->url = $argArray['url'];

    /* These fields may or may not be set-- 
       They may also be defined by setting id (was url) & using getRecord() */
    $this->title = $argArray['title'];
    $this->description = $argArray['description'];
    $this->contributor = $argArray['contributor'];
    $this->date = $argArray['date'];
    // initialize lastModified to same as date, at first
    $this->lastModified = $argArray['date'];

    // define subjects for this record, if set
    if ($subjArray) {
      $this->subject = $subjArray;
    } else {
      $this->subject = array();
    }
    
    // pass in tamino settings to subject list
    $this->all_subjects = new subjectList($argArray);

    // FIXME: edits?
    $this->edit = array();

  }  // end linkRecord constructor

  // generate an xquery using current settings, depending on the mode
  function xquery ($mode) {
    // Dublin Core namespace
    $dcns = 'dc="http://purl.org/dc/elements/1.1/"';

    switch ($mode):
  case 'getRecord': 
    // xquery to retrieve a record by id
    $query = "declare namespace $dcns
                  for \$b in input()/linkCollection/linkRecord 
                  where \$b/@id = '$this->id' 
                 return \$b"; 
    break;
  case 'delete':
    // xquery to delete record by url
    $query = "declare namespace $dcns 
              update for \$b in input()/linkCollection/linkRecord
              where \$b/@id = '$this->id'
              do delete \$b";
    break;
  case 'add':
    // xquery to add a new record
    $query = "declare namespace $dcns
              update for \$b in input()/linkCollection 
              do insert <linkRecord id='$this->id'>
                 <dc:title>$this->title</dc:title>
                 <dc:identifier>$this->url</dc:identifier>
                 <dc:description>$this->description</dc:description>
                 <dc:date>$this->date</dc:date>
                 <dc:contributor>$this->contributor</dc:contributor>"; 
    foreach ($this->subject as $s) {
      $query .= "<dc:subject>$s</dc:subject>\n";
    }
    $query .= '</linkRecord> into $b';
    break;
  case 'modify': 
    // xquery to modify an existing record
   $query = "declare namespace $dcns
              update for \$b in input()/linkCollection/linkRecord
              where \$b/@id = '$this->id' 
              do replace \$b with <linkRecord id='$this->id'>
                <dc:title>$this->title</dc:title>
                <dc:identifier>$this->url</dc:identifier>
                <dc:description>$this->description</dc:description>
                <dc:date>$this->date</dc:date>
                <dc:contributor>$this->contributor</dc:contributor>";
   foreach ($this->subject as $s) {
     $query .= "<dc:subject>$s</dc:subject>\n";
   }
   foreach ($this->edit as $e) {
     $query .= "<edit>
                  <dc:date>$e->date</dc:date> 
                  <dc:description>$e->description</dc:description> 
                  <dc:contributor>$e->contributor</dc:contributor> 
               </edit>"; 
   }
   $query .= '</linkRecord>';
   break;
   endswitch;

   return $query;
  }

  // create an id (for a new record)
  function generateId() {
    $this->id = "$this->date $this->contributor";
  }


  // retrieve a record from tamino by url & initialize object values
  function taminoGetRecord() {
    $rval = $this->tamino->xquery($this->xquery('getRecord'));
    if ($rval) {
      print "<p>LinkRecord Error: failed to retrieve linkRecord from Tamino.<br>";
      print "(Tamino error code $rval)</p>";
    } else {            // xquery succeeded
      $xmlRecord = $this->tamino->xml->getBranches("ino:response/xq:result/linkRecord");

      // id should be set initially (key value)
      //      $this->id = $this->tamino->xml->getTagAttribute("id", "ino:response/xq:result/linkRecord");
      if ($xmlRecord) {
	// Cycle through all of the branches (so order won't matter)
	foreach ($xmlRecord as $branch) {
	  if ($val = $branch->getTagContent("dc:identifier")) {
	    $this->url = $val;
	  } else if ($val = $branch->getTagContent("dc:title")) {
	    $this->title = $val;
	  } else if ($val = $branch->getTagContent("dc:description")) {
	    $this->description = $val;
	  } else if ($val = $branch->getTagContent("dc:date")) {
	    $this->date = $val;
	    $this->lastModified = $this->date;
	  } else if ($val = $branch->getTagContent("dc:contributor")) {
	    $this->contributor = $val;
	  } else if ($val = $branch->getTagContent("dc:subject")) {
	    array_push($this->subject, $val);
	  }       
	}
	// get any editing information


	$edits = $this->tamino->xml->getBranches("ino:response/xq:result/linkRecord/edit");
	//	$edits = $xmlRecord[0]->getBranches("edit");
	if ($edits) {
	  // arrays to store values temporarily, to get into linkEdit objects
	  $mydate = array();
	  $mycontrib = array();
	  $mydesc = array();
	  
	  foreach ($edits as $branch) {
	    if ($val = $branch->getTagContent("dc:date")) { 
	      array_push($mydate, $val);
	      // compare dates & save most recent
	      if ($val > $this->lastModified) {
		$this->lastModified = $val;
	      }
	    } else if ($val = $branch->getTagContent("dc:description")) {
	      array_push($mydesc,$val);
	    } else if ($val = $branch->getTagContent("dc:contributor")) {
	      array_push($mycontrib, $val);
	    }
	  }
	  for ($i=0; $i < count($mydate); $i++) {
	    $edit_args = array('date' => $mydate[$i], 
			       'contributor' => $mycontrib[$i],
			       'description' => $mydesc[$i]);
	    $this->addEdit($edit_args);
	  }
	}

      } else {
	print "<p>LinkRecord Error: no linkRecord found in XML response.<br>";
      }
    }
  }

  // Delete record from tamino
  function taminoDelete (){
    $rval = $this->tamino->xquery($this->xquery('delete'));
    if ($rval) {
      print "<p>Failed to delete linkRecord for <b>$this->title</b>.</p>";
    } else {
      print "<p>Successfully deleted record for <b>$this->title</b>.</p>";
    }
  }

  // add a record to tamino
  function taminoAdd () {
    if (($this->id == '')||(!(isset($this->id)))) {
      $this->generateId();
    }
    /* 
    print "DEBGU taminoAdd: xquery  is " . $this->xquery('add') . "<br>";
    */

    $rval = $this->tamino->xquery($this->xquery('add'));
    if ($rval) {          // tamino error
      print "<p>Failed to add new record for <b>$this->url</b>.</p>";
    } else {          // success
      print "<p>Successfully added new record for <b>$this->url</b>.</p>";
    }
  }

  // update a record in tamino
  function taminoModify () {
    $rval = $this->tamino->xquery($this->xquery('modify'));
    if ($rval) {       // tamino error
      print "<p>Failed to update record for <b>$this->url</b>.</p>";
    } else {
      print "<p>Successfully updated record for <b>$this->url</b>.</p>";
    }
  }
  
  // print a brief listing of the record
  function printSummary () {
    print "<p><a href='$this->url'>$this->title</a><br>";
    print "<font size='-1'>$this->url</font><br>";
    print "$this->description<br>";
    print "<font size='-2'>Contributed by: $this->contributor</font><br>";
    print "</p>";
  }


  // print all the values in a nice HTML table
  function printHTML ($show_edits = 1) {
    print "<p><table border='1' width='100%'>";
    print "<tr><th width='20%'>Title:</th><td>$this->title</td></tr>";
    print "<tr><th>ID:</th><td>$this->id</td></tr>";    
    print "<tr><th>URL:</th><td><a href='$this->url'>$this->url</a></td></tr>";
    print "<tr><th>Subject(s):</th><td>";
    foreach ($this->subject as $s) { print "$s<br>"; }
    print "</td></tr>";
    print "<tr><th>Description:</th><td>$this->description</td></tr>";
    print "<tr><th>Contributor:</th><td>$this->contributor</td></tr>";
    print "<tr><th>Date Submitted:</th><td>$this->date</td></tr>";
    if ($this->date != $this->lastModified) {
      // only print last modified if it is different than submitted
      print "<tr><th>Date Last Modified:</th><td>$this->lastModified</td></tr>";
    }
    if ($show_edits && (count($this->edit) > 0)) {
      print "<tr><th>Modifications</th><td>";
      foreach ($this->edit as $e) { 
	$e->printEdit(); 
      }
      print "</td></tr>";
    }
    print "</table></p>";
  }

  // create an HTML form, with initial values set (if defined)
  // mode should be either add or modify
  function printHTMLForm ($mode) {
    $textinput  = "input type='text' size='50'";
    $hiddeninput = "input type='hidden'";
    $readonlyinput = "input type='text' readonly='yes' size='50'";
    print "<table border='1' align='center'>\n";
    print "<form action='do_$mode.php' method='get'>\n";
    print "<tr><th>Title:</th><td><$textinput name='title' value='$this->title'></td></tr>\n";
    print "<tr><th>URL:</th><td>\n";
    if (isset($this->url)) {
      print "<$textinput name='url' value='$this->url'>";
    } else {
      print "<$textinput name='url' value='http://'>";
    }
    print "</td></tr>\n";
    print "<tr><th>Description</th><td><textarea cols='50' rows='4' name='desc'>$this->description</textarea></td></tr>\n";
    print "<tr><th>Subject(s):</th>\n";
    print "<td>";
    $this->all_subjects->printSelectList($this->subject);
    print "<br><i>Note: use control to select more than one subject.</i>\n";
    print "</td></tr>\n";
    print "<tr><th>Submitted by:</th><td>\n";
    // if already defined, don't allow user to modify
    if (isset($this->contributor)) {
      //      print "<$hiddeninput name='contrib' value='$this->contributor'>$this->contributor";
      print "<$readonlyinput name='contrib' value='$this->contributor'>\n";
    } else {
      print "<$textinput name='contrib'>\n";
    }
    print "</td></tr>";
    print "<tr><th>Date Submitted:</th><td>\n";
    // if already defined, don't allow user to modify
    if (isset($this->date)) {
      print "<$readonlyinput name='date' value='$this->date'>\n";
    } else {
      /* If unset, initialize date value to today.  
         Format is: 2004-04-09 4:13 PM */
      print "<$textinput name='date' value='" . date("Y-m-d g:i A") . "'>\n";
    }
    print "</td></tr>";
    if (isset($this->id)) {
      print "<tr><th>Record ID:</th><td><$readonlyinput name='id' value='$this->id'></td></tr>\n";
    }

    // Fields to keep track of changes to a record
    if ($mode == 'modify') {

      print "<tr><th colspan='2'>Modification Data</th></tr>\n";

      if (count($this->edit) > 0) {
	print "<tr><th>Previous edits:</th><td>\n";
	foreach ($this->edit as $e) { 
	  $e->printEdit(); 
	  print "<$hiddeninput name='prev_date[]' value='" . $e->date . "'>\n";
	  print "<$hiddeninput name='prev_contrib[]' value='" . $e->contributor . "'>\n";
	  print "<$hiddeninput name='prev_desc[]' value='" . $e->description . "'>\n";
	}
	print "</td></tr>";
      }

      print "<tr><th colspan='2'>Current Edit</th></tr>\n";

      print "<tr><th>Edited by:</th><td><$textinput name='mod_contrib'></td></tr>\n";
      print "<tr><th>Description of change:</th>\n";
      print "<td><textarea cols='50' rows='4' name='mod_desc'></textarea></td></tr>\n";
      print "<tr><th>Date Modified:</th><td><$textinput name='mod_date' value='" . date("Y-m-d g:i A") . "'></td></tr>\n";
    }
    print "<tr><td colspan='2' align='center'>\n";
    print "<input type='submit' value='Submit'>\n";
    print "<input type='reset'>\n";
    print "</td></tr></form></table>\n";
  }

  function addEdit ($argArray) {
    // create a new linkEdit with specified settings
    $myedit = new linkEdit($argArray);
    // add to array of edits for this linkRecord
    array_push($this->edit, $myedit);
  }

}



// simple class to keep track of edits to a record
class linkEdit {
  var $date;
  var $contributor;
  var $description;

  function linkEdit ($argArray) {
    $this->date = $argArray['date'];
    $this->contributor = $argArray['contributor'];
    $this->description = $argArray['description'];
  }
  
  // print edit information in a nice table
  function printEdit () {
    print "<table border='1' width='80%'>";
    print "<tr><th width='20%'>Editor:</th><td>$this->contributor</td></tr>";
     print "<tr><th>Description:</th><td>$this->description</td></tr>";
    print "<tr><th>Date:</th><td>$this->date</td></tr>";
   print "</table>";
  }
   
}