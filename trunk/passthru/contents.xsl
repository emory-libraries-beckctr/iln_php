<?xml version="1.0" encoding="ISO-8859-1"?> 

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:html="http://www.w3.org/TR/REC-html40" version="1.0"
	xmlns:ino="http://namespaces.softwareag.com/tamino/response2" 
	xmlns:xql="http://metalab.unc.edu/xql/">

<xsl:variable name="mode_name">Contents</xsl:variable> 
<xsl:variable name="base_url">http://tamino.library.emory.edu/passthru/servlet/transform/tamino/BECKCTR/ILN</xsl:variable>
<xsl:variable name="xslurl">&amp;_xslsrc=xsl:stylesheet/</xsl:variable>
<xsl:variable name="xsl_imgview">imgview.xsl</xsl:variable>

<xsl:variable name="total_count" select="count(//div1 | //div2[count(figure) > 1])" />

<!-- <xsl:include href="ilnshared.xsl"/> -->

<xsl:output method="html"/>  

<xsl:template match="/"> 
  <xsl:call-template name="proc_instr" /> 
  <xsl:call-template name="html_head" /> 

  <!-- begin body -->
  <xsl:element name="body">
    <xsl:attribute name="onload">toggle_init(<xsl:value-of select="$total_count"/>)</xsl:attribute>

  <!-- header -->
  <xsl:copy-of
  select="document('http://chaucer.library.emory.edu/iln/head.xml')" />
  <!-- sidebar -->
  <xsl:copy-of
  select="document('http://chaucer.library.emory.edu/iln/sidebar.xml')" />

  <xsl:element name="div">
     <xsl:attribute name="class">content</xsl:attribute>
	<xsl:call-template name="html_title" /> 

        <xsl:apply-templates select="//div1"/>

  </xsl:element>

  <!-- footer -->
  <xsl:copy-of
  select="document('http://chaucer.library.emory.edu/iln/foot.xml')"/> 

</xsl:element>  <!-- end body -->

</xsl:template>


<xsl:template match="div1"> 
<!-- get number : necessary for collapsible list id -->
<xsl:variable name="num"><xsl:number level="any" count="//div1 | //div2[count(figure) > 1]" /></xsl:variable>
  <xsl:element name="p"> 

   <!-- create toggle image -->
   <xsl:element name="a">
     <xsl:attribute name="onclick">toggle_ul('list<xsl:value-of select="$num"/>')</xsl:attribute>
     <xsl:element name="img">
       <xsl:attribute name="src">http://chaucer.library.emory.edu/iln/closed.gif</xsl:attribute>
       <xsl:attribute name="id">gif_list<xsl:value-of select="$num"/></xsl:attribute>
     </xsl:element> <!-- img -->
   </xsl:element> <!-- a -->
   
   <!-- make volume title clickable also -->
   <xsl:element name="a">
     <xsl:attribute name="onclick">toggle_ul('list<xsl:value-of select="$num"/>')</xsl:attribute>
   <xsl:apply-templates select="head"/> <!-- title -->
 </xsl:element> <!-- a -->
 <xsl:element name="font">
 <xsl:attribute name="size">-1</xsl:attribute>
 - <xsl:value-of select="./@type"/>   <!-- type (volume) -->
 - <xsl:value-of select="docDate" />  <!-- date -->
 - (<xsl:value-of select="count(div2)"/> Articles) <!-- number of articles -->
  </xsl:element> <!-- end font -->
  </xsl:element>  <!-- end p -->

 <xsl:element name="ul">
   <xsl:attribute name="id">list<xsl:value-of select="$num"/></xsl:attribute>
 <xsl:apply-templates select="div2"/>
 </xsl:element>

</xsl:template>


<xsl:template match="div2"> 
<xsl:variable name="num"><xsl:number level="any" count="//div1 | //div2[count(figure) > 1]" /></xsl:variable>
 <xsl:element name="li">
   <xsl:if test="count(figure) > 1">
     <xsl:attribute name="class">container</xsl:attribute>
   </xsl:if>

  <xsl:if test="count(figure) > 1">
   <!-- create toggle image -->
   <xsl:element name="a">
     <xsl:attribute name="onclick">toggle_ul('list<xsl:value-of select="$num"/>')</xsl:attribute>
     <xsl:element name="img">
       <xsl:attribute name="src">http://chaucer.library.emory.edu/iln/closed.gif</xsl:attribute>
       <xsl:attribute name="id">gif_list<xsl:value-of select="$num"/></xsl:attribute>
     </xsl:element> <!-- img -->
   </xsl:element> <!-- a -->
  </xsl:if>
   

  <!-- if no title, label as untitled -->
  <xsl:element name="a">
   <xsl:attribute name="href"><xsl:value-of
   select="$base_url"/>?_xql=TEI.2//div1/div2[@id='<xsl:value-of select="@id"/>']&amp;_xslsrc=xsl:stylesheet/ilnbrowse.xsl</xsl:attribute>  
  <xsl:if test="head = ''">Untitled</xsl:if>
  <xsl:apply-templates select="head"/>
  </xsl:element> <!-- a -->

 <xsl:element name="font">
 <xsl:attribute name="size">-1</xsl:attribute>
  - <xsl:value-of select="./@type"/>
  - <xsl:value-of select="bibl/date" /> 
  <xsl:if test="bibl/extent">
      - (<xsl:value-of select="bibl/extent" />)
  </xsl:if>
  </xsl:element> <!-- end font -->
</xsl:element> <!-- end li -->

<xsl:if test="count(figure) > 1">

<xsl:element name="ul">
  <xsl:attribute name="id">list<xsl:value-of select="$num"/></xsl:attribute>
  <xsl:apply-templates select="figure"/>
</xsl:element>
</xsl:if>
  
</xsl:template>

<xsl:template match="figure">
    <xsl:element name="li">
      <xsl:value-of select="head"/>
      [<xsl:element name="a">
  <xsl:attribute name="href">javascript:launchViewer('<xsl:value-of
      select="concat($base_url,'?_xql=TEI.2//figure[@entity=')"/>\'<xsl:value-of select="./@entity"/>\'<xsl:value-of select="concat(']', $xslurl,
	   $xsl_imgview)"/>')</xsl:attribute>view image</xsl:element>] <!-- end a --> 
  </xsl:element>  <!-- end li -->
</xsl:template>

<!-- set processing instructions -->
<xsl:template name="proc_instr">
  <xsl:processing-instruction name="cocoon-format">
    type="text/html"
  </xsl:processing-instruction>
</xsl:template>

<!-- define html head -->
<xsl:template name="html_head">
  <xsl:element name="head">
  <!-- FIXME: how to vary 1st part of title according to mode? -->
  <xsl:element name="title">
  <xsl:value-of select="$mode_name" /> - The Civil War in America from The Illustrated London News
  </xsl:element> <!-- title -->
  <xsl:element name="script">
    <xsl:attribute name="language">Javascript</xsl:attribute>
    <xsl:attribute name="src">http://chaucer.library.emory.edu/iln/image_viewer/launchViewer.js</xsl:attribute>
  </xsl:element><!-- script -->
  <xsl:element name="script">
    <xsl:attribute name="language">Javascript</xsl:attribute>
    <xsl:attribute name="src">http://chaucer.library.emory.edu/iln/iln_functions.js</xsl:attribute>
  </xsl:element> <!-- script -->
  <script language="javascript">
   getBrowserCSS();
  </script>

  <!-- addition to basic html_head from ilnshared -->
  <xsl:element name="script">
    <xsl:attribute name="language">Javascript</xsl:attribute>
    <xsl:attribute name="src">http://chaucer.library.emory.edu/iln/content-list.js</xsl:attribute>
  </xsl:element> <!-- script -->

  <xsl:element name="link">
    <xsl:attribute name="rel">stylesheet</xsl:attribute>
    <xsl:attribute name="type">text/css</xsl:attribute>
    <xsl:attribute name="href">http://chaucer.library.emory.edu/iln/contents.css</xsl:attribute>
  </xsl:element>


  </xsl:element> <!-- head -->
</xsl:template>

<xsl:template name="html_title">
     <xsl:element name="h2"><xsl:value-of select="$mode_name" /></xsl:element>
</xsl:template>

</xsl:stylesheet>
