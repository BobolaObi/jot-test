<?php
/**
 * Exports Data to CSV
 * @package JotForm_Utils
 * @copyright Copyright (c) 2009, Interlogy LLC
 */

namespace Legacy\Jot\Utils;

class CSV{
    
    private $data;
    public $debug = false;
    private $contents;
    
    function __construct($data){
        $this->data = $data;
    }
    
    public function setData($data){
        $this->data = $data;
    }
    
    /**
     * Generate a CSV
     * @return 
     */
    public function generate(){
        foreach($this->data as $line){
            $length = count($line);
            foreach($line as $i => $column){                
                $comma = $i >= $length-1? '' : ',';
                
                # Apperantly no body followed the CSV standards 
                # $column = utf8_encode($column);     // Encode UTF characters
                # $column = addslashes($column);      // Add slashes to escape data
                # $column = preg_replace('/\n\r|\r\n|\n|\r/', '\n', $column);     // Escape new lines to protect data
                # I had to disable these
                
                # Strip HTML tags from CSV files
                $column = str_replace('<br>', " ", $column);
                $column = strip_tags($column);
                # escape values
                $column = str_replace('"', '""', $column);                        // Escape double quotes to keep data in one column 
                
                $this->contents .= '"'.$column.'"'.$comma;
            }
            $this->contents .= "\n";
        }
    }
    
    /**
     * Prints a table for debugging
     * @return 
     */
    public function generateTable($title = 'Data Table'){
        $this->contents  = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
        $this->contents .= "\n<html>\n<head>\n";
        $this->contents .= ' <meta http-equiv="Content-Type" content="text/html; charset=utf-8">'."\n";
        $this->contents .= ' <link rel="stylesheet" href="'.HTTP_URL.'opt/tablesorter/themes/blue/style.css" type="text/css" media="print, projection, screen" />'."\n";
        $this->contents .= ' <style>'."\n";
        $this->contents .= '   body, html{ height:100%; width:100%; margin:0px; padding:0px; }'."\n";
        $this->contents .= '   body{ font-family:Verdana, Geneva, Arial, Helvetica, sans-serif; font-size:12px; }'."\n";
        $this->contents .= '   th{ background:#ddd; }'."\n";
        $this->contents .= '   tr:nth-child(even){ background:#f5f5f5; }'."\n";
        $this->contents .= '   th, td{ text-align:center; border:1px solid #bbb; }'."\n";
        $this->contents .= '   table{ border-collapse:collapse; margin:5px; }'."\n";
        $this->contents .= '   table.tablesorter thead tr th, table.tablesorter tfoot tr th { padding:5px 19px 5px 10px; }'."\n"; 
        $this->contents .= '   #thumb{position:absolute;border:1px solid #ccc;background:#333;padding:5px;display:none;color:#fff;}';
        $this->contents .= ' </style>'."\n";
        $this->contents .= ' <script type="text/javascript" src="'.HTTP_URL.'opt/tablesorter/jquery-latest.js?v1.6"></script>'."\n";
        $this->contents .= ' <script type="text/javascript" src="'.HTTP_URL.'opt/tablesorter/jquery.tablesorter.min.js"></script>'."\n";
        $this->contents .= ' <script>'."\n";
        $this->contents .= '    var thumbPreview = function(){'."\n"; 
        $this->contents .= '        var xOffset = 10;'."\n";
        $this->contents .= '        var yOffset = 30;'."\n";
        $this->contents .= '        $("a.thumb").hover('."\n";
        $this->contents .= '            function(e){'."\n";
        $this->contents .= '                this.t = this.title;this.title = "";'."\n";    
        $this->contents .= '                var c = (this.t != "") ? "<br/>" + this.t : "";'."\n";
        $this->contents .= '                $("body").append("<p id=\'thumb\'><img src=\'"+ this.href +"\' alt=\'url preview\' />"+ c +"</p>");'."\n";                                
        $this->contents .= '                $("#thumb").css("top",(e.pageY - xOffset) + "px").css("left",(e.pageX + yOffset) + "px").fadeIn("fast");'."\n";                        
        $this->contents .= '                },'."\n";
        $this->contents .= '            function(){'."\n";
        $this->contents .= '                this.title = this.t;$("#thumb").remove();'."\n";
        $this->contents .= '            }'."\n";
        $this->contents .= '        ); '."\n";
        $this->contents .= '        $("a.thumb").mousemove(function(e){$("#thumb").css("top",(e.pageY - xOffset) + "px").css("left",(e.pageX + yOffset) + "px");});'."\n";
        $this->contents .= '    }'."\n";
        $this->contents .= '    $(document).ready(function(){'."\n";
        $this->contents .= '        $.tablesorter.addWidget({id: "repeatHeaders",format: function(table) {if(!this.headers) {this.headers = []; var h = this.headers; $("thead th",table).each(function() {h.push("<th>" + $(this).text() + "</th>");});}$("tr.repated-header",table).remove();for(var i=0; i < table.tBodies[0].rows.length; i++) {if((i%31) == 30) {$("tbody tr:eq(" + i + ")",table).before($("<tr></tr>").addClass("repated-header").html(this.headers.join("")));}}}});'."\n";
        $this->contents .= '        var addImages = function(links){';
        $this->contents .= '            $.each(links, function(){';
        $this->contents .= '                var l = this.innerHTML.split("/");';
        $this->contents .= '                var file = l.pop();';
        $this->contents .= '                if(/\.\bpng\b|\bjpg\b|\bjpeg\b|\bgif\b|\bbmp\b|\btiff\b|\btif\b$/gim.test(file)){';
        $this->contents .= '                    file = "<img src=\"'.HTTP_URL.'images/magnifier.png\" align=\"absmiddle\" style=\"margin-right:5px;\" />"+file;';
        $this->contents .= '                    this.className = "thumb";';
        $this->contents .= '                }';
        $this->contents .= '                this.innerHTML = file+"<br>";';
        $this->contents .= '            });';
        $this->contents .= '        };'."\n";
        $this->contents .= '        $("body").linkify({handleLinks:addImages});'."\n";
        $this->contents .= '        $("body").linkify({handleLinks:addImages});'."\n";
        $this->contents .= '        $("#data-table").tablesorter({widgets: ["zebra","repeatHeaders"]});'."\n";
        $this->contents .= '        thumbPreview();';
        $this->contents .= '    });'."\n";
        $this->contents .= ' </script>'."\n";
        $this->contents .= " <title>".$title."</title>\n</head>\n<body>\n";
        $this->contents .= ' <table id="data-table" class="tablesorter" border="1" cellpadding="8" cellspacing="0">'."\n";
        
        foreach($this->data as $ix => $line){
            
            if($ix == 0){ $this->contents .= "  <thead>\n"; }
            if($ix == 1){ $this->contents .= "  <tbody>\n"; }
            
            $this->contents .= "   <tr>\n";
            
            foreach($line as $i => $column){
                if($ix == 0){
                    $this->contents .= '    <th nowrap>'.($column?  $column : "-").'</th>'."\n";
                }else{
                    
                    $this->contents .= '    <td>'.($column?  $column : "-").'</td>'."\n";
                }
            }
            
            $this->contents .= "   </tr>\n";
            if($ix == 0){ $this->contents .= "  </thead>\n"; }
        }
        $this->contents .= "  </tbody>\n </table>\n</body>\n</html>";
    }
    
    /**
     * Print Contents on the screen
     * @return 
     */
    public function printOnScreen($plain = false){
        if($plain){
            header("Content-type: text/plain; charset=utf-8");
        }else{
            header("Content-type: text/html; charset=utf-8");
        }
        echo $this->contents;
    }
    
    /**
     * Prompts a download screen then streams the file
     * @param  $filename
     * @return 
     */
    public function downloadFile($filename){
        
        $filename = preg_replace('/\W+/', '-', $filename);
        
        header('Content-Type: application/csv; charset=utf-8'); 
        header("Content-length: " . mb_strlen($this->contents, 'utf8')); 
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"; charset=utf-8'); 
        header("Pragma: public");
        
        echo $this->contents;
        
        exit;
    }
    
}