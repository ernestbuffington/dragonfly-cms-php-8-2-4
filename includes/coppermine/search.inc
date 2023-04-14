<?php 
/***************************************************************************  
   Coppermine 1.3.1 for CPG-Dragonfly™
  **************************************************************************
   Port Copyright (c) 2004-2005 CPG Dev Team
   http://dragonflycms.com/
  **************************************************************************
   v1.1 (c) by Grégory Demar http://coppermine.sf.net/
   This program is free software; you can redistribute it and/or modify       
   it under the terms of the GNU General Public License as published by       
   the Free Software Foundation; either version 2 of the License, or          
   (at your option) any later version.                                        
  **************************************************************************  
  Last modification notes:
  $Source: /public_html/includes/coppermine/search.inc,v $
  $Revision: 9.9 $
  $Author: nanocaiordo $
  $Date: 2008/01/31 15:22:51 $
****************************************************************************/
if (!defined('CPG_NUKE')) { die("You can't access this file directly..."); } 
/**
 * functions_search.php
 *                                 -------------------
 *        begin                : Wed Sep 05 2001
 *        copyright            : (C) 2001 The phpBB Group
 *        email                : support@phpbb.com
 * 
 *        $Id: search.inc,v 9.9 2008/01/31 15:22:51 nanocaiordo Exp $
 */
// encoding match for workaround
$multibyte_charset = 'utf-8, big5, shift_jis, euc-kr, gb2312';
$charset = _CHARSET;
$mb_charset = stristr($multibyte_charset, $charset);

function clean_words(&$entry, $mb_charset)
{
    global $charset, $multibyte_charset;

    static $drop_char_match = array('^', '$', '&', '(', ')', '<', '>', '`', '\'', '"', '|', ',', '@', '_', '?', '%', '~', '.', '[', ']', '{', '}', ':', '\\', '/', '=', '#', '\'', ';', '!');
    static $drop_char_replace = array(' ', ' ', ' ', ' ', ' ', ' ', ' ', '', '', ' ', ' ', ' ', ' ', '', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ' , ' ', ' ', ' ', ' ', ' ', ' ');

    $entry = ' ' . strtolower($entry) . ' '; 
    // Replace line endings by a space
    $entry = preg_replace('/[\n\r]/is', ' ', $entry); 
    // + and - becomes and & not
    $entry = str_replace(' +', ' and ', $entry);
    $entry = str_replace(' -', ' not ', $entry); 
    // Filter out strange characters like ^, $, &, change "it's" to "its"
    if (!$mb_charset) for($i = 0; $i < count($drop_char_match); $i++) {
        $entry = str_replace($drop_char_match[$i], $drop_char_replace[$i], $entry);
    } 
    // 'words' that consist of <3 or >20 characters are removed.
    // $entry = preg_replace('/\b([a-z0-9]{1,2}|[a-z0-9]{21,})\b/',' ', $entry);
    return $entry;
} 

function search_pics($search_string, $select_columns, $query_all, $limit, &$pic_count, $set_caption)
{
require_once(CORE_PATH.'nbbcode.php');
global $CONFIG, $mb_charset,$db;
$pic_count = 0;
$rowset = array();
if ($query_all) {
    $search_string = str_replace('*', '%', $search_string);
    if ($select_columns != '*') $select_columns .= ', caption';
    $result = $db->sql_query("SELECT SQL_CALC_FOUND_ROWS $select_columns FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS.")
    WHERE MATCH (p.title, p.caption, p.keywords, p.filename, p.user1, p.user2, p.user3, p.user4) AGAINST ('$search_string' /*!40100 IN BOOLEAN MODE*/ ) AND approved=1
    GROUP BY pid $limit");
    $rowset = $db->sql_fetchrowset($result);
    list($pic_count) = $db->sql_ufetchrow("SELECT FOUND_ROWS()",SQL_NUM);
    $db->sql_freeresult($result);
    if (!$pic_count){
        if ($select_columns != '*') $select_columns .= ', caption';
        $result = $db->sql_query("SELECT SQL_CALC_FOUND_ROWS $select_columns FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS.")
        WHERE MATCH (p.title, p.caption, p.keywords, p.filename, p.user1, p.user2, p.user3, p.user4) AGAINST ('$search_string' /*!40100 IN BOOLEAN MODE*/ ) AND approved=1
        GROUP BY pid $limit");
        $rowset = $db->sql_fetchrowset($result);
        list($pic_count) = $db->sql_ufetchrow("SELECT FOUND_ROWS()",SQL_NUM);
        $db->sql_freeresult($result);
    }
    //finally if nothing else works we try the old way
    if (!$pic_count) {
        $search_string = '%' . $search_string . '%';
        $search_string = str_replace('%%', '%', $search_string);
        $result = $db->sql_query("SELECT count(*) FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS.")
        WHERE a.title LIKE '$search_string' OR  p.title LIKE '$search_string' OR p.caption LIKE '$search_string' OR p.keywords LIKE '$search_string' OR p.filename LIKE '$search_string' OR p.user1 LIKE '$search_string' OR p.user2 LIKE '$search_string' OR p.user4 LIKE '$search_string'  AND approved=1
        GROUP BY pid $limit");
        list($pic_count) = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);
        $result = $db->sql_query("SELECT $select_columns FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS.")
        WHERE a.title LIKE '$search_string' OR p.title LIKE '$search_string' OR p.caption LIKE '$search_string' OR p.keywords LIKE '$search_string' OR p.filename LIKE '$search_string' OR p.user1 LIKE '$search_string' OR p.user2 LIKE '$search_string' OR p.user4 LIKE '$search_string' AND approved=1
        GROUP BY pid $limit");
        $rowset = $db->sql_fetchrowset($result);
        $db->sql_freeresult($result);
    }
    if (isset($set_caption)) foreach ($rowset as $key => $row) {
        $caption = $rowset[$key]['title'] ? "<span class=\"thumb_title\">" . $rowset[$key]['title'] . "</span>" : '';
        if ($CONFIG['caption_in_thumbview']) {
            $caption .= $rowset[$key]['caption'] ? "<span class=\"thumb_caption\">" . decode_bbcode($rowset[$key]['caption']) . "</span>" : '';
        } 
        $rowset[$key]['caption_text'] = $caption;
        
    }
} elseif ($search_string != '') {
    $split_search = array();
    $split_search = split(' ', clean_words($search_string, $mb_charset));

    $current_match_type = 'and';

    $pic_set = '';
    for($i = 0; $i < count($split_search); $i++) {
        switch ($split_search[$i]) {
            case 'and':
                $current_match_type = 'and';
                break;

            case 'or':
                $current_match_type = 'or';
                break;

            case 'not':
                $current_match_type = 'not';
                break;

            default:
                if (empty($split_search[$i])) break;
                
                $word = Fix_Quotes($split_search[$i]);
                $keyword = Fix_Quotes($split_search[$i]);
                $match_word = '%' . str_replace('*', '%', $word) . '%';
                $match_keyword = '%' . str_replace('*', '%', $keyword) . '%';
                        
                //$match_word = '%' . str_replace('*', '%', $split_search[$i])) . '%';
                //$match_keyword = '% ' . str_replace('*', '%', Fix_Quotes($split_search[$i])) . ' %';

                $sql = "SELECT pid FROM {$CONFIG['TABLE_PICTURES']} p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS.") WHERE CONCAT('', keywords, '') LIKE '$match_keyword' ";

                if ($query_all) $sql .= "OR filename LIKE '$match_word' " . "OR title LIKE '$match_word' " . "OR caption LIKE '$match_word' " . "OR user1 LIKE '$match_word' " . "OR user2 LIKE '$match_word' " . "OR user3 LIKE '$match_word' " . "OR user4 LIKE '$match_word' ";
                $result = $db->sql_query($sql);
                if ($db->sql_numrows($result)) {
                    $set = '';
                    while ($row = $db->sql_fetchrow($result)) {
                        $set .= $row['pid'] . ',';
                    } // while
                    if (empty($pic_set)) {
                        if ($current_match_type == 'not') {
                            $pic_set .= ' pid not in (' . substr($set, 0, -1) . ') ';
                        } else {
                            $pic_set .= ' pid in (' . substr($set, 0, -1) . ') ';
                        } 
                    } else {
                        if ($current_match_type == 'not') {
                            $pic_set .= ' and pid not in (' . substr($set, 0, -1) . ') ';
                        } else {
                            $pic_set .= ' ' . $current_match_type . ' pid in (' . substr($set, 0, -1) . ') ';
                        } 
                    } 
                } 

                $db->sql_freeresult($result);

                $current_match_type = 'and';
        } 
    } 

    if (!empty($pic_set)) {
        /*
        $result = $db->sql_query($sql,false,__FILE__,__LINE__);
        $nbEnr = $db->sql_fetchrowset($result);
        $pic_count = $nbEnr[0];
        */
        $result = $db->sql_query("SELECT COUNT(*) FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS.") WHERE ($pic_set) AND approved=1 GROUP BY pid");
        $pic_count = $db->sql_numrows($result);
        $db->sql_freeresult($result);
        if ($select_columns != '*') $select_columns .= ', p.title, caption';
        $rowset = $db->sql_ufetchrowset("SELECT $select_columns FROM {$CONFIG['TABLE_PICTURES']} AS p INNER JOIN {$CONFIG['TABLE_ALBUMS']} AS a ON (p.aid = a.aid AND ".VIS_GROUPS.") WHERE ($pic_set) AND approved=1 GROUP BY pid $limit",SQL_BOTH);
        if ($set_caption) foreach ($rowset as $key => $row) {
            $caption = $rowset[$key]['title'] ? "<span class=\"thumb_title\">" . $rowset[$key]['title'] . "</span>" : '';
            if ($CONFIG['caption_in_thumbview']) {
                $caption .= $rowset[$key]['caption'] ? "<span class=\"thumb_caption\">" . decode_bbcode($rowset[$key]['caption']) . "</span>" : '';
            } 
            $rowset[$key]['caption_text'] = $caption;
            
        }
         
    }
}
return $rowset;
}