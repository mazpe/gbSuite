<?php

/******BEGIN LICENSE BLOCK*******
* 
* Common Public Attribution License Version 1.0.
*
* The contents of this file are subject to the Common Public Attribution 
* License Version 1.0 (the "License") you may not use this file except in 
* compliance with the License. You may obtain a copy of the License at
* http://developers.facebook.com/fbopen/cpal.html. The License is based 
* on the Mozilla Public License Version 1.1 but Sections 14 and 15 have 
* been added to cover use of software over a computer network and provide 
* for limited attribution for the Original Developer. In addition, Exhibit A 
* has been modified to be consistent with Exhibit B.
* Software distributed under the License is distributed on an "AS IS" basis, 
* WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License 
* for the specific language governing rights and limitations under the License.
* The Original Code is Facebook Open Platform.
* The Original Developer is the Initial Developer.
* The Initial Developer of the Original Code is Facebook, Inc.  All portions 
* of the code written by Facebook, Inc are 
* Copyright 2006-2008 Facebook, Inc. All Rights Reserved.
*
*
********END LICENSE BLOCK*********/


// Facebook Copyright 2006 - 2008

include_once $_SERVER['PHP_ROOT'].'/lib/core/parameter.php';

/**
 * Turns a query string into a dictionary
 * @param   string    $query_string
 * @return  dict      {<key>: <val>, <key2>: <val2>, ...}
 *
 * Since parse_str uses magic quotes and works by mutating an
 * existing array, this function is useful to get a noslashes
 * version of a fresh array, which is what we usually want.
 *
 */
function qs_vars($query_string) {
  $dict = array();
  parse_str($query_string, $dict);
  $no_slashes_dict = array();
  foreach ($dict as $key => $val) {
    $no_slashes_dict[$key] = noslashes($val);
  }
  return $no_slashes_dict;
}

