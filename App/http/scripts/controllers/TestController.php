<?php
class Scripts_TestController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function loopInsert() {

        $db = DB();

        echo "Loop Insert start<br/>";

        try {

            $db->startTransaction();
            
            $n = 11;
            for($i=$n; $i<$n+5; $i++) {


                if( $i == 14 ) {
                    $res = $db->fetchAll("SELECT * FROM test_table");
                    echo "<pre>";
                    echo "Result<br/>";
                    print_r($res);
                    echo "</pre>";
                    die;
                }

            }

            $db->commit();

        } catch(Exception $e) {

            $db->rollback();

            throw $e;
        }

    }


    public function indexAction(TinyPHP_Request $request) {
        
        $db = DB();
        
        $res = $db->fetchAll("SELECT * FROM test_table");
        echo "<pre>";
        echo "Result<br/>";
        print_r($res);
        echo "</pre>";
        die;        

        try {

            $db->startTransaction();

            $id = $db->insert("test_table", ["id" => NULL, "name" => "Virat Kohli", "email" => "virat@yrcoder.com", "created_at" => date("Y-m-d H:i:s")]);

            echo "Inserted Id: {$id}<br/>";

            $this->loopInsert();

            $db->commit();

        } catch(Exception $e) {
            $db->rollback();
            echo "ERROR: ".$e->getMessage();
        }        
    }
}
?>
