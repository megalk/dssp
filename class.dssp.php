<?php

class TableData {
 
     private $_db;

    public function __construct() {

        try {
            $host        = 'localhost';
            $database    = 'db';
            $user        = 'root';
            $passwd        = 'pass';
        
            $this->_db = new PDO('mysql:host='.$host.';dbname='.$database, $user, $passwd, array(PDO::ATTR_PERSISTENT => true));
        } catch (PDOException $e) {
            error_log("Failed to connect to database: ".$e->getMessage());
        }        
        
    }

    public function get($query,$index_column,$columns,$where,$data) {
               extract($data);
               $str = $where;
               eval("\$str = \"$str\";");
               $table=$query;
               
        $sLimit = "";
        if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' ) {
            $sLimit = "LIMIT ".intval( $_GET['iDisplayStart'] ).", ".intval( $_GET['iDisplayLength'] );
        }
        
        // Ordering
        $sOrder = "";
        if ( isset( $_GET['iSortCol_0'] ) ) {
            $sOrder = "ORDER BY  ";
            for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ ) {
                if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" ) {
                    $sortDir = (strcasecmp($_GET['sSortDir_'.$i], 'ASC') == 0) ? 'ASC' : 'DESC';
                    $sOrder .= "`".$columns[ intval( $_GET['iSortCol_'.$i] ) ]."` ". $sortDir .", ";
                }
            }
            
            $sOrder = substr_replace( $sOrder, "", -2 );
            if ( $sOrder == "ORDER BY" ) {
                $sOrder = "";
            }
        }
        
        $sWhere = "";
        if ( isset($_GET['sSearch']) && $_GET['sSearch'] != "" ) {
            $sWhere = "WHERE (";
            for ( $i=0 ; $i<count($columns) ; $i++ ) {
                if ( isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i] == "true" ) {
                    $sWhere .= "`".$columns[$i]."` LIKE :search OR ";
                }
            }
            $sWhere = substr_replace( $sWhere, "", -3 );
            $sWhere .= ')';
        }
        
        // Individual column filtering
        for ( $i=0 ; $i<count($columns) ; $i++ ) {
            if ( isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' ) {
                if ( $sWhere == "" ) {
                    $sWhere = "WHERE ";
                }
                else {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`".$columns[$i]."` LIKE :search".$i." ";
            }
        }
                
               if( $wre != "" ){
            if ( $sWhere == "" ) {
                    $sWhere = "WHERE ";
            }
                else {
                    $sWhere .= " AND ";
            }
            $sWhere .=$str;
               }
        
             
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS `".str_replace(" , ", " ", implode("`, `", $columns))."` FROM ".$table." ".$sWhere." ".$sOrder." ".$sLimit;
        $statement = $this->_db->prepare($sQuery);
        
        if ( isset($_GET['sSearch']) && $_GET['sSearch'] != "" ) {
            $statement->bindValue(':search', '%'.$_GET['sSearch'].'%', PDO::PARAM_STR);
        }
        for ( $i=0 ; $i<count($columns) ; $i++ ) {
            if ( isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' ) {
                $statement->bindValue(':search'.$i, '%'.$_GET['sSearch_'.$i].'%', PDO::PARAM_STR);
            }
        }

        $statement->execute();
        $rResult = $statement->fetchAll();
        
        $iFilteredTotal = current($this->_db->query('SELECT FOUND_ROWS()')->fetch());
        
        // Get total number of rows in table
        $sQuery = "SELECT COUNT(`".$index_column."`) FROM ".$table."";
        $iTotal = current($this->_db->query($sQuery)->fetch());
        
        // Output
        $output = array(
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array()
        );
        
        // Return array of values
        foreach($rResult as $aRow) {
            $row = array();            
            for ( $i = 0; $i < count($columns); $i++ ) {
                if ( $columns[$i] == "version" ) {
                    // Special output formatting for 'version' column
                    $row[] = ($aRow[ $columns[$i] ]=="0") ? '-' : $aRow[ $columns[$i] ];
                }
                else if ( $columns[$i] != ' ' ) {
                    $row[] = $aRow[ $columns[$i] ];
                }
            }
            $output['aaData'][] = $row;
        }
        
 echo json_encode( $output );
                
    }

}

header('Pragma: no-cache');
header('Cache-Control: no-store, no-cache, must-revalidate');
if (isset($_GET['tableID'])) {   
$table_data = new TableData();
include('ConnectionManager.php');
$newDb = new ConManagerMIMS();
$newDb->getConnection();
$resultDT=$newDb->db->query('select * from dynamic_table where id="'.$_GET['tableID'].'"');
$menuP=$resultDT->fetch_array();
$columns = explode("||",$menuP['columns']);
$table_data->get($menuP['view'],$menuP['index_column'],$columns,$menuP['where'],$_GET);
 
}



?>
