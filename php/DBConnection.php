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

class DBConnection {
   // Database Connection constants

   var $mDBName = PR2_DBNAME;
   var $mDBUser = PR2_DBUSER;
   var $mDBPasswd = PR2_DBPASSWD;
   var $mDBHost = PR2_DBHOST;

   var $mDBConn;
   var $mTmpResults;
   var $mQueryResult;

   var $mCurrentTransaction;
   var $mObjectID;
   var $mTransactionPackedIP;

   function DBConnection() {
      $this->mDBConn = mysql_connect($this->mDBHost, $this->mDBUser, 
       $this->mDBPasswd);

      if (! $this->mDBConn) {
         error_log(mysql_error());
         $this->mDBConn = null;
         return;
      }   

      if (! mysql_select_db($this->mDBName, $this->mDBConn)) {
         $this->Disconnect();
         return;
      }

      $this->mCurrentTransaction = null;
      $this->mTransactionPackedIP = null;
      $this->mTmpResults = array();
      $this->mQueryResult = null;
   }

   function Connected() {
      return (!is_null($this->mDBConn));
   }

   function Quote($unquotedVar) {
      return mysql_escape_string($unquotedVar); 
   }

   function SelectRow($sqlQuery) {
      assert($this->Connected());

      $this->mQueryResult = mysql_query($sqlQuery, $this->mDBConn);

      if (! $this->mQueryResult)
         return null;
      
      $this->mTmpResults = array(); //Clear out old mTmpResults

      $this->mTmpResults = mysql_fetch_assoc($this->mQueryResult);

      mysql_free_result($this->mQueryResult);
      return $this->mTmpResults;
   }

   function SelectAll($sqlQuery) {
      assert($this->Connected());

      $this->mQueryResult = mysql_query($sqlQuery, $this->mDBConn);

      if (! $this->mQueryResult)
         return null;

      $this->mTmpResults = array(); //Clear out old mTmpResults

      while ($row = mysql_fetch_assoc($this->mQueryResult))
         array_push($this->mTmpResults, $row);

      mysql_free_result($this->mQueryResult);
      return $this->mTmpResults;
   }

   function SelectSingleColumn($sqlQuery) {
      assert($this->Connected());

      $this->mQueryResult = mysql_query($sqlQuery, $this->mDBConn);

      if (! $this->mQueryResult)
         return null;

      $this->mTmpResults = array(); //Clear out old mTmpResults

      while ($row = mysql_fetch_array($this->mQueryResult))
         array_push($this->mTmpResults, $row[0]);

      mysql_free_result($this->mQueryResult);
      return $this->mTmpResults;
   }

   function DoOp($sqlQuery) {
      assert($this->Connected());

      $this->mQueryResult = mysql_query($sqlQuery, $this->mDBConn);

      if (! $this->mQueryResult)
         return null;
      else
         return mysql_affected_rows($this->mDBConn);
   }

   function LastInsertID() {
      assert($this->Connected());
      return mysql_insert_id($this->mDBConn);
   }

   function GetObjectID() {
      assert($this->Connected());
      return $this->mObjectID;
   }

   function BeginTransaction($transType, $object = 0) {
      assert($this->Connected());

      if (!is_null($this->mCurrentTransaction))
         return null;
      else if (! preg_match("/^[-\w]+$/", $transType))
         return null;
      else if (! preg_match("/-new$/", $transType) && $object == 0)
         return null;
      else if (! preg_match("/^\d+$/", $object))
         return null;

      if ($object == 0) {
         if ($this->DoOp("INSERT INTO or_objects (created) VALUES 
          (NOW())") != 1)
            return null;

         $this->mObjectID = $this->LastInsertID();
      }

      $transactionInfo = $this->SelectRow("SELECT type FROM 
       or_transaction_types WHERE action='$transType'");

      if (is_null($transactionInfo['type']))
         return null;

      $this->mTransactionPackedIP = PackIPAddress(getenv('REMOTE_ADDR'));

      if ($this->DoOp("INSERT INTO or_transactions (type, starttime, 
       objectid, ipaddr) VALUES ('" . $transactionInfo['type'] . "', NOW(), 
       '$this->mObjectID', '$this->mTransactionPackedIP')") != 1)
         return null;

      $this->mCurrentTransaction = $this->LastInsertID();

      // Record the hostname after the transactions has 'started', but last
      $this->RecordIPAddrHostname();
      return $this->mCurrentTransaction;
   }

   function EndTransaction() {
      assert($this->Connected());

      if (is_null($this->mCurrentTransaction))
         return true;

      if ($this->DoOp("UPDATE or_transactions SET endtime=NOW(), 
       state='complete' WHERE eventid='$this->mCurrentTransaction'") != 1)
         return null;

      $this->mObjectID = null;
      $this->mCurrentTransaction = null;
      $this->mTransactionPackedIP = null;
      return true;
   } 

   function TransactionPending() {
      // if mCurrentTransaction isn't null, then return true, since a
      // Transaction IS pending
      return (! is_null($this->mCurrentTransaction));
   }

   function RecordIPAddrHostname() {
      assert($this->TransactionPending());
      assert(preg_match('/^[0-9a-f]{8}$/i', $this->mTransactionPackedIP));

      // The 0 Class A is IANA reserved, so we use it as a special purpose "IP
      // address" to identify administrators making changes and such; thus,
      // we should never log 0.x.y.z IP addrs.
      if (0 == hexdec(substr($this->mTransactionPackedIP, 0, 2)))
         return;

      $unpackedip = UnpackIPAddress($this->mTransactionPackedIP); 
      $hostname = gethostbyaddr($unpackedip);

      if ($hostname != $unpackedip) {
         $iprecorded = false;
         $hostInfo = $this->SelectAll("SELECT hostid, hostname FROM 
          or_hostnames WHERE ipaddr='$this->mTransactionPackedIP'");

         foreach ($hostInfo as $hostRecord) {
            if ($hostRecord['hostname'] == $hostname) {
               $this->DoOp("UPDATE or_hostnames SET lastseen=NOW() WHERE
                hostid=" . $hostRecord['hostid']);
               $iprecorded = true;
               break;
            }
         }

         if (!$iprecorded) {
            $sqlHostname = $this->Quote($hostname);
            $this->DoOp("INSERT INTO or_hostnames (hostname, ipaddr, recorded,
             lastseen) VALUES ('$sqlHostname', '$this->mTransactionPackedIP', 
             NOW(), NOW())"); 
         }
      }
   }

   function Disconnect() {
      if ($this->Connected()) {
         mysql_close($this->mDBConn);
         $this->mDBConn = null;
      }
   }
}

function GetCachedDBConnection() {
   if (isset($GLOBALS['_CACHED_GLOBAL_DB_CONNECTION'])) {
      if (! $GLOBALS['_CACHED_GLOBAL_DB_CONNECTION']->Connected())
         $GLOBALS['_CACHED_GLOBAL_DB_CONNECTION'] = new DBConnection();
   }
   else {
      $GLOBALS['_CACHED_GLOBAL_DB_CONNECTION'] = new DBConnection();
   }

   // Ensure connection has no pending transactions; it's an error if
   // there are pending transactions
   assert($GLOBALS['_CACHED_GLOBAL_DB_CONNECTION']->Connected());
   assert(! $GLOBALS['_CACHED_GLOBAL_DB_CONNECTION']->TransactionPending());
   return $GLOBALS['_CACHED_GLOBAL_DB_CONNECTION']; 
}

function PackIPAddress($ipaddr) {
   $oct = split("\.", $ipaddr);
   return sprintf("%02x%02x%02x%02x", $oct[0], $oct[1], $oct[2], $oct[3]);
}

function UnpackIPAddress($packedip = null) {
   assert(preg_match('/^[0-9a-f]{8}$/i', $packedip));
   $octone = hexdec(substr($packedip, 0, 2));
   $octtwo = hexdec(substr($packedip, 2, 2));
   $octthree = hexdec(substr($packedip, 4, 2));
   $octfour = hexdec(substr($packedip, 6, 2));

   return sprintf('%d.%d.%d.%d', $octone, $octtwo, $octthree, $octfour);
}

?>
