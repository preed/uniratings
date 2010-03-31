<?php
/*
 * The contents of this file are subject to the Jabber Open Source License 
 * Version 1.0 (the "License").  You may not copy or use this file, in either 
 * source code or executable form, except in compliance with the License.  You 
 * may obtain a copy of the License at http://www.jabber.com/license/ or at 
 * http://www.opensource.org/.
 * 
 * Read the LICENSE file included with the source distribution for more
 * information.
 *
 * Software distributed under the License is distributed on an "AS IS" basis, 
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License 
 * for the specific language governing rights and limitations under the 
 * License.
 *
 * Copyrights:
 *
 * Portions created by or assigned to Polyratings.com are Copyright (c) 
 * 1999-2002 Polyratings.com.  All Rights Reserved.  Contact information for 
 * Polyratings.com, is available at http://www.polyratings.com/.
 *
 * Portions Copyright (c) 2001-2002 J. Paul Reed
 *
 * Contributor(s):
 *
 */

include_once('Constants.php');
include_once(PHP_ROOT . 'UserInterface.php');
include_once(UI_ROOT . 'errors.inc');

class UIHandle {
   var $mRenderingMap;
   var $mIncludeMap;

   var $mBackendData;

   function UIHandle() {
      $this->mRenderingMap = $GLOBALS['uiRenderingMap'];
      $this->mIncludeMap = $GLOBALS['uiIncludeMap'];
      $this->mBackendData = array();
   }

   function PutValue($key = null, $value = null) {
      if (is_null($key)) {
         error_log("Null key '$key' passed to UIHandle::PutValue");
         assert(false); 
      } 

      if (is_null($value)) {
         error_log("Null value with key '$key' passed to UIHandle::PutValue");
         assert(false);
      }

      $this->mBackendData[$key] = &$value;   // We copy the ref to save
                                             // time/memory 
   }

   function GetValue($key = null) {
      assert(!is_null($key));
      return $this->mBackendData[$key];
   }

   function ClearValues() {
      $this->mBackendData = array();
   }

   function PrintPage($page = null) {
      assert(!is_null($page));

      if (array_key_exists($page, $this->mRenderingMap)) {
         $funcPtr = $this->mRenderingMap[$page];
      }
      else {
         $this->ErrorPage("Unregistered rendering handler for page '$page'");
         return;
      }

      if (array_key_exists($page, $this->mIncludeMap)) {
         if (file_exists(UI_ROOT . $this->mIncludeMap[$page])) {
            include_once(UI_ROOT . $this->mIncludeMap[$page]);
         }
         else {
            $this->ErrorPage("Error including file for page '$page'");
            return;
         }
      }

      if (function_exists($funcPtr))
         $funcPtr($this);
      else
         $this->ErrorPage("Unknown rendering handler for page '$page'");
   }

   // ErrorPage is a special function because we want to be able to pass it
   // special info, if necessary, to help debugging; right now, it's pretty
   // simple
   function ErrorPage($mesg = null) {
      PrintErrorPage($mesg);
   }
}

?>
