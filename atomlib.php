<?php
/**
 * This library provides functions to generate Atom feeds and tries to follow the same API as lib/rsslib.php
 *
 * @author Matt Clarkson <mattc@catalyst.net.nz>
 * @package oublog
 */


//This function return all the common atom headers

function atom_standard_header($uniqueid, $link, $updated, $title = NULL, $description = NULL) {

    global $CFG, $USER, $OUTPUT;

    static $pixpath = '';

    $status = true;
    $result = "";

    if (!$site = get_site()) {
        $status = false;
    }

    if ($status) {

        //Calculate title, link and description
        if (empty($title)) {
            $title = format_string($site->fullname);
        }

        //xml headers
        $result .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $result .= "<feed xmlns=\"http://www.w3.org/2005/Atom\">\n";

        //open the channel
        //write channel info
        $result .= atom_full_tag('id',1, false, htmlspecialchars($uniqueid));
        $result .= atom_full_tag('updated', 1, false, date_format_rfc3339($updated));
        $result .= atom_full_tag('title', 1, false, htmlspecialchars(html_to_text($title)));
        $result .= atom_full_tag('link', 1, false, null,array('href'=>$link,'rel'=>'self'));
        if(!empty($description)) {
            $result .= atom_full_tag('subtitle', 1, false, $description);
        }
        $result .= atom_full_tag('generator', 1, false, 'Moodle');
        $today = getdate();
        $result .= atom_full_tag('rights', 1, false, '&#169; '. $today['year'] .' '. format_string($site->fullname));

        //write image info
        $atompix = $OUTPUT->pix_url('i/rsssitelogo');

        //write the info
        $result .= atom_full_tag('logo', 1, false, $atompix);

    }

    if (!$status) {
        return false;
    } else {
        return $result;
    }
}


function atom_add_items($items) {

    global $CFG;

    $result = '';
    $xhtmlattr = array('type'       => 'xhtml');

    if (!empty($items)) {
        foreach ($items as $item) {
            $result .= atom_start_tag('entry',1,true);
            $result .= atom_full_tag('title',2,false,htmlspecialchars(html_to_text($item->title)));
            $result .= atom_full_tag('link', 2, false, null, array('href' => $item->link, 'rel'=>'alternate'));
            $result .= atom_full_tag('updated',2,false,date_format_rfc3339($item->pubdate));
            //Include the author if exists
            if (isset($item->author)) {
                $result .= atom_start_tag('author',2,true);
                $result .= atom_full_tag('name',3,false, $item->author);
                $result .= atom_end_tag('author',2,true);
            }
            $result .= atom_full_tag('content',2,false,'<div xmlns="http://www.w3.org/1999/xhtml">'.clean_text($item->description, FORMAT_HTML).'</div>', $xhtmlattr);
            $result .= atom_full_tag('id',2,false,$item->link);
            if (isset($item->tags)) {
                $tagdata = array();
                if (isset($item->tagscheme)) {
                    $tagdata['scheme'] = $item->tagscheme;
                }
                foreach($item->tags as $tag) {
                    $tagdata['term'] = $tag;
                    $result .= atom_full_tag('category', 2, true, false, $tagdata);
                }
            }
            $result .= atom_end_tag('entry',1,true);

        }
    } else {
        $result = false;
    }
    return $result;
}


//This function return all the common footers for every rss feed in the site
function atom_standard_footer($title = NULL, $link = NULL, $description = NULL) {

    global $CFG, $USER;

    $status = true;
    $result = '';

    ////Close the rss tag
    $result .= '</feed>';

    return $result;
}



//Return the xml start tag
function atom_start_tag($tag,$level=0,$endline=false,$attributes=null) {
    if ($endline) {
       $endchar = "\n";
    } else {
       $endchar = "";
    }
    $attrstring = '';
    if (!empty($attributes) && is_array($attributes)) {
        foreach ($attributes as $key => $value) {
            $attrstring .= " ".$key."=\"".htmlspecialchars($value)."\"";
        }
    }
    return str_repeat(" ",$level*2)."<".$tag.$attrstring.">".$endchar;
}

//Return the xml end tag
function atom_end_tag($tag,$level=0,$endline=true) {
    if ($endline) {
       $endchar = "\n";
    } else {
       $endchar = "";
    }
    return str_repeat(" ",$level*2)."</".$tag.">".$endchar;
}


//Return the start tag, the contents and the end tag
function atom_full_tag($tag,$level=0,$endline=true,$content,$attributes=null) {
    global $CFG;
    $st = atom_start_tag($tag,$level,$endline,$attributes);
    if($content === false) {
        $st = preg_replace('~>$~',' />', $st);
        return $st;
    }
    $co="";
    $co = preg_replace("/\r\n|\r/", "\n", $content);
    $et = atom_end_tag($tag,0,true);

    return $st.$co.$et;
}


function date_format_rfc3339($timestamp=0) {

    $date = date('Y-m-d\TH:i:s', $timestamp);

    $matches = array();

    if (preg_match('/^([\-+])(\d{2})(\d{2})$/', date('O', $timestamp), $matches)) {
        $date .= $matches[1].$matches[2].':'.$matches[3];
    } else {
        $date .= 'Z';
    }
    return $date;
}

