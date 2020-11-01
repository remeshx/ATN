<?php
//Adjacency To Nestedset converter for big data step by step
/**************************************************************************
 * @file        ATN.php
 * @author      Reza Meshkat
 * @email       Meshkat@ymail.com
 * @site        RezaMeshkat.info
 * @date        2018
 */


Class ATN {

    public function __construct()
    {
        $this->mysqli = $this->connectDB();
        
    }

    public function reset()
    {
        $this->DB_query('drop table '. Temporary_table_prefix .'susers_nestedset');
        $this->DB_query('drop table '. Temporary_table_prefix .'susers_nestedset_mem');
        $this->DB_query('drop table '. Temporary_table_prefix .'susers_nestedset_temp_memory');
        $this->DB_query('drop table '. Temporary_table_prefix .'meshop_susers_mem');
        
        return true;
    }

    public function startConversion()
    {
        $result = $this->DB_query('select * from '. Temporary_table_prefix .'susers_nestedset_temp_memory',false);
        if (!$result or $result->num_rows<=0) {
            $this->createTemporaryTables();
            $result = $this->DB_query('select * from '. Temporary_table_prefix .'susers_nestedset_temp_memory');
        }
        list($id,$uid,$pid,$cid,$lev,$pcid,$left,$step) = $result->fetch_array();
        $initial_step=$step;

        if ($step==0 and $uid==0){
            $uid= Table_Top_child_id;
            $left=0;
            $step=0;
            $this->DB_query('truncate '. Temporary_table_prefix .'susers_nestedset_mem ;');
            $this->DB_query('truncate '. Temporary_table_prefix .'meshop_susers_mem ;');
            $this->DB_query('insert into '. Temporary_table_prefix .'meshop_susers_mem (id,parentId) select '.Table_child_Field.','.Table_parent_Field.' from '.Table_Name.';');
        }
        $finalrow=false;


        while (true) {
            if ($step!=0 and $uid==0) {

                if (Table_Depth_Field!='') $depthField = ', A.'.Table_Depth_Field.' = B.`lev` ';
                $result = $this->DB_query('UPDATE '.Table_Name.' A LEFT JOIN `'. Temporary_table_prefix .'susers_nestedset_mem` B ON A.'.Table_child_Field.' = B.uid SET A.'.Table_LFT_Field.' = B.lft, A.'.Table_RGT_Field.' = B.rgt '.$depthField.' WHERE B.id IS NOT NULL'); 
                $this->echoOut("Updated rows : " . $this->mysqli->affected_rows);	
                
                $this->DB_query('drop table '. Temporary_table_prefix .'susers_nestedset');
                $this->DB_query('drop table '. Temporary_table_prefix .'susers_nestedset_mem');
                $this->DB_query('drop table '. Temporary_table_prefix .'susers_nestedset_temp_memory');
                $this->DB_query('drop table '. Temporary_table_prefix .'meshop_susers_mem');
               
                $this->echoOut("FINISHED.");
                exit;
            }

            if (($initial_step+ step_limit)<$step)
                if ($finalrow) break;
            
            $res_userExists = $this->DB_query('select pid,cid,lev from '. Temporary_table_prefix .'susers_nestedset_mem where uid=' . $uid);
            if ($res_userExists->num_rows > 0) 
            {
                list($pid,$pcid,$lev) = $res_userExists->fetch_array();
                $exists=true;
            } else {
                $pcid=0;
                $exists=false;
                $lev++;
            }
    
            $haschild = false;
            $cid=0;
            
            $res_childs = $this->DB_query("select id from ".Temporary_table_prefix."meshop_susers_mem where parentId=$uid and id>$pcid order by id limit 1");
            
            if ($res_childs->num_rows > 0)
            {
                $haschild = true;
                list($cid) = $res_childs->fetch_array();
            } else $haschild = false;
            
    
            if ($haschild) 
            {
                if ($exists)
                {
                    $this->DB_query("update ".Temporary_table_prefix."susers_nestedset_mem set cid=$cid where uid=$uid");
                } else {
                    $left++;
                    $this->DB_query("insert into ".Temporary_table_prefix."susers_nestedset_mem (uid,lft,rgt,lev,cid,pid) values ($uid,$left,0,$lev,$cid,$pid)");
                }
                $pid= $uid;
                $uid= $cid;
            } else {
                if ($exists)
                {
                    $this->DB_query("update ".Temporary_table_prefix."susers_nestedset_mem set rgt=$left+1 where uid=$uid");
                } else {
                    $left++;
                    $this->DB_query("insert into ".Temporary_table_prefix."susers_nestedset_mem (uid,lft,rgt,lev,cid,pid) values ($uid,$left,$left+1,$lev,0,$pid)");
                }
                $uid=$pid;
                $left++;
                $finalrow=true;
            }
            $step++;
        }

        $this->DB_query("update ".Temporary_table_prefix."susers_nestedset_temp_memory set uid=$uid ,pid=$pid ,cid=$cid ,lev=$lev ,pcid=$pcid ,`left`=$left, step=$step ");
        $this->mysqli->close();
        $this->echoOutRefresh($step,$left);        
    }

    public function connectDB(){
        $mysqli = new mysqli(Database_host,Database_user,Database_pass,Database_name);

        // Check connection
        if ($mysqli -> connect_errno) {
            echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
            exit();
        } 
        return $mysqli;
    }

    public function DB_query($sql,$DieOnError=true)
    {
        $result = $this->mysqli->query($sql);
        if (!$result) {
            if ($DieOnError)
            {
            echo "Error Query : $sql";
            exit;
            } else {
                return false;
            }
        }
        return $result;
    }

    public function createTemporaryTables()
    {
        $this->DB_query('drop table if Exists '. Temporary_table_prefix .'susers_nestedset');
        $this->DB_query("      
        CREATE TABLE IF NOT EXISTS `". Temporary_table_prefix ."susers_nestedset` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `uid` bigint(20) NOT NULL,
            `lft` int(11) NOT NULL DEFAULT '0',
            `rgt` int(11) NOT NULL DEFAULT '0',
            `lev` int(11) NOT NULL DEFAULT '0',
            `cid` bigint(20) NOT NULL DEFAULT '0',
            `pid` bigint(20) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `uid` (`uid`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;
        ",true);


        $this->DB_query('drop table if Exists '. Temporary_table_prefix .'susers_nestedset_mem');
        $this->DB_query("      
        CREATE TABLE IF NOT EXISTS `". Temporary_table_prefix ."susers_nestedset_mem` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `uid` bigint(20) NOT NULL,
            `lft` int(11) NOT NULL DEFAULT '0',
            `rgt` int(11) NOT NULL DEFAULT '0',
            `lev` int(11) NOT NULL DEFAULT '0',
            `cid` bigint(20) NOT NULL DEFAULT '0',
            `pid` bigint(20) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `uid` (`uid`)
        ) ENGINE=Memory  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;
        ",true);
        

        $this->DB_query('drop table if Exists '. Temporary_table_prefix .'susers_nestedset_temp_memory');
        $this->DB_query("
        CREATE TABLE IF NOT EXISTS `". Temporary_table_prefix ."susers_nestedset_temp_memory` (
            `id` int(11) NOT NULL  AUTO_INCREMENT,
            `uid` bigint(20) NOT NULL DEFAULT '0',
            `pid` bigint(20) NOT NULL DEFAULT '0',
            `cid` bigint(20) NOT NULL DEFAULT '0',
            `lev` int(11) NOT NULL DEFAULT '0',
            `pcid` bigint(20) NOT NULL DEFAULT '0',
            `left` int(11) NOT NULL DEFAULT '0',
            `step` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`)
        ) ENGINE=Memory DEFAULT CHARSET=latin1;",true);
        


        $this->DB_query('drop table if Exists '. Temporary_table_prefix .'meshop_susers_mem');
        $this->DB_query("
        CREATE TABLE IF NOT EXISTS `". Temporary_table_prefix ."meshop_susers_mem` (
            `id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT 'کد کاربر',
            `parentId` bigint(20) NOT NULL COMMENT 'کد کاربر مادر',
            `lft` int(11) NOT NULL DEFAULT '0',
            `rgt` int(11) NOT NULL DEFAULT '0',
            `depth` mediumint(9) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            KEY `parentId` (    `parentId`),
            KEY `lft` (`lft`),
            KEY `id` (`id`),
            KEY `parentId_2` (`parentId`)
        ) ENGINE=Memory DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;") ;

        $this->DB_query('insert into '. Temporary_table_prefix .'susers_nestedset_temp_memory set id=1' );
    }


    public function echoOutRefresh($step,$left)
    {
        echo "<P>Step: $step 
        <br/>Left: $left ";


        if (AutoRefresh>0) echo "
        <br/>time out : ".AutoRefresh." ms
            <script>
                setTimeout(function(){
                window.location.reload(1);
                }, ".AutoRefresh.");
            </script>
        ";
        else echo(time() . ' <p>Done. refresh page to continue or set AutoRefresh On in config file.');

        exit;
    }


    public function echoOut($text){
        echo '<P>' . $text;
    }

}

?>