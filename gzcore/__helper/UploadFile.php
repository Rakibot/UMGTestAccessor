<?php

namespace GZCore\__helper;
use \GZCore\__framework\db\GZORM;
use \GZCore\__framework\db\GZWhere;
use \GZCore\__framework\GZUtils;
use \GZCore\__framework\GZConfig;
use \GZCore\__framework\GZErrorHandler;
use \Exception;

class UploadFile extends GZHelper {
    private $basePath;
    private $folders;
    private $tables;
    private $pks;
    private $fields;
    private $deleteRows;
    private $method;
    private $urls;

    public function __construct(){
        $this->init();
        if($this->method == "GET"){
            throw new Exception("La petición no puede ser servida a travez del metodo GET", 403);
        }
    }

    private function init(){
        $config = GZConfig::$config->uploadFile;
        $this->basePath = $config->basePath;
        $this->folders = $config->folder;
        $this->tables = $config->dbTable;
        $this->pks = $config->dbPKField;
        $this->fields = $config->dbField;
        $this->deleteRows = $config->dbDeleteRow;
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->urls = $config->baseUrl;
    }

    /*
     * The file will be contains an similar schema at these:
     *  {
     *      file: {
     *          name: String,
     *          content: String
     *      },
     *      user_id: integer
     *      property_id: Integer
     *  	property_name: string
     *  }
     */
    public function base64(array $payload){
        //var_dump(file_get_contents('php://input'));//leer estaa parte y usar parse_str para hacer el parseo
        $this->validateFields($payload);

        if((!array_key_exists("file",$payload) && $this->method != "DELETE")
        || (!array_key_exists("pk",$payload) && $this->method != "POST")
        ){
            throw new Exception("Needed fields not foud",1101);
        }
        $folder = $payload["folder"];
        if(!array_key_exists($folder,$this->folders)){
            throw new Exception("Folder not configured",1102);
        }

        if($this->method == "DELETE"){
            return $this->deleteFile($payload,$folder);
        }else{

            $file = $payload["file"];
            if(!array_key_exists("content",$file)){
                throw new Exception("File is empty",1102);
            }

            $fileName = null;

            if(array_key_exists("pk",$payload)){

                $tableContent = GZORM::select($this->tables[$folder])
                    ->addWhere(new GZWhere($this->pks[$folder],"=",$payload["pk"]))
                    ->doQuery();
                if(empty($tableContent)){
                    throw new Exeption("No records found",1001);
                }
                $tableContent = $tableContent[0];
                $fileName = $tableContent[$this->fields[$folder]];
                if(!empty($fileName)){
                    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                    $name = pathinfo($fileName, PATHINFO_FILENAME);
                    $fileName = $name.".$ext";
                }else{
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $uuid=GZUtils::getUuid();
                    $fileName = $uuid.".$ext";
                }
            }else{
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $uuid=GZUtils::getUuid();
                $fileName = $uuid.".$ext";

                $obj = GZORM::insert($this->tables[$folder])
                            ->addValues([ $this->fields[$folder] => $fileName ])
                            ->doQuery();

                if(empty($obj)){
                    throw new Exception("No records stored",1000);
                }
                $payload["pk"] = $obj[$this->pks[$folder]];
            }

            $filePath = GZUtils::pathJoin(GZUtils::getCurrentPath(), $this->basePath, $this->folders[$folder], $fileName);

            $stored = $this->storeIn($filePath,$file["content"]);

            $url = null;
            if($stored){
                $url = $this->getWebUrl($fileName,$folder);


                $data = GZORM::update($this->tables[$folder])
                    ->addValues([ $this->fields[$folder] => $url ])
                    ->addWhere(new GZWhere($this->pks[$folder],"=",$payload["pk"]))
                    ->doQuery();

                $stored = !empty($data);

            }
            return ["file" => $file["name"],"uploaded"=> $stored, "url" => $url ];
        }
    }
    public function file(array $payload) {
        if(empty($payload)){
            $payload = $_POST;
        }
        $this->validateFields($payload);

        if((!array_key_exists("file",$_FILES) && $this->method != "DELETE")
        || (!array_key_exists("pk",$payload) && $this->method != "POST")
        ){
            throw new Exception("Needed fields not foud",1101);
        }

        $folder = $payload["folder"];

        if($this->method == "DELETE"){
            return $this->deleteFile($payload,$folder);
        }else{
            $fileName = null;
            $file = $_FILES["file"];
            if(array_key_exists("pk",$payload)){

                $tableContent = GZORM::select($this->tables[$folder])
                    ->addWhere(new GZWhere($this->pks[$folder],"=",$payload["pk"]))
                    ->doQuery();
                if(empty($tableContent)){
                    throw new Exeption("No records found",1001);
                }
                $tableContent = $tableContent[0];
                $fileName = $tableContent[$this->fields[$folder]];
                if(!empty($fileName)){
                    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                    $name = pathinfo($fileName, PATHINFO_FILENAME);
                    $fileName = $name.".$ext";
                }else{
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $uuid=GZUtils::getUuid();
                    $fileName = $uuid.".$ext";
                }
            }else{
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $uuid=GZUtils::getUuid();
                $fileName = $uuid.".$ext";

                $obj = GZORM::insert($this->tables[$folder])
                            ->addValues([ $this->fields[$folder] => $fileName ])
                            ->doQuery();

                if(empty($obj)){
                    throw new Exception("No records stored",1000);
                }
                $payload["pk"] = $obj[$this->pks[$folder]];
            }
            $filePath = GZUtils::pathJoin(GZUtils::getCurrentPath(), $this->basePath, $this->folders[$folder], $fileName);
            $stored = copy($file["tmp_name"],$filePath);
            $url = null;
            if($stored){
                $url = $this->getWebUrl($fileName,$folder);
                $data = GZORM::update($this->tables[$folder])
                    ->addValues([ $this->fields[$folder] => $url ])
                    ->addWhere(new GZWhere($this->pks[$folder],"=",$payload["pk"]))
                    ->doQuery();

                $stored = !empty($data);
            }
            return ["file" => $file["name"],"uploaded"=> $stored, "url" => $url ];
        }


        /*
        $this->onlyFor('POST');
        if (empty($payload['property_id']) || empty($payload['file']) || empty($payload['property_name']) || empty($payload['file_name'])|| empty($payload['user_id'])) {
            throw new Exception('The property_id, file content, property_name, file_name and the user id are required', 1000);
        }

        $file = $payload['file'];
        $property_name=$payload['property_name'];
        $userId=$payload['user_id'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $path = realpath(GZUtils::pathJoin(GZUtils::getCurrentPath(), '..', 'attach_files'));
        $uuid=GZUtils::getUuid();
        $fileName =  $uuid.".$ext";
        $filePath = GZUtils::pathJoin($path, $userId, $property_name, $fileName);
        $this->storeIn($filePath, $file['content']);

        return GZORM::insert('property_files')
                ->addValues([
                    'property_id' => $payload['property_id'],
                    'name' => $file['name'],
                    'file_url' => $this->getWebUrl($filePath),
                    'file_name' => $payload['file_name']
                ])
                ->doQuery();*//*
        $this->validateFields($payload);
        return[];*/
    }

    private function deleteFile($payload,$folder){

        $wherePK = new GZWhere($this->pks[$folder],"=",$payload["pk"]);
            
        $fileData = GZORM::select($this->tables[$folder])
                            ->addWhere($wherePK)
                            ->doQuery();
        if(empty($fileData)){
            throw new Exception("No records found",1001);
        }
        $file = $fileData[0];
        $res = true;
        $fileName = "";
        if(!empty($file[$this->fields[$folder]])){
            $ext = pathinfo($file[$this->fields[$folder]], PATHINFO_EXTENSION);
            $name = pathinfo($file[$this->fields[$folder]], PATHINFO_FILENAME);
            $fileName = $name.".$ext";
            $filePath = GZUtils::pathJoin(GZUtils::getCurrentPath(), $this->basePath, $this->folders[$folder], $fileName);
            
            $res = unlink($filePath);
        }

        if($res){
            if($this->deleteRows[$folder]){
                $del = GZORM::delete($this->tables[$folder])
                            ->addWhere($wherePK)
                            ->doQuery();
                if(!empty($del)){
                    $res = $del[0];
                    if($res !== true){
                        $res = false;
                    }
                }
            }else{
                if(!empty($file[$this->fields[$folder]])){
                    $upd = GZORM::update($this->tables[$folder])
                                ->addWhere($wherePK)
                                ->addValues([$this->fields[$folder] => null])
                                ->doQuery();
                    $res = !empty($upd);
                }
            }
        }
        return ["file" => $fileName,"deleted"=> $res, "url" => null ];

    }

    private function validateFields($payload){
        if(!array_key_exists("folder",$payload) 
        //|| !array_key_exists("",$_POST) 
        ){
            GZErrorHandler::printError("Payload -- ".implode( $payload));
            throw new Exception("Needed fields not foud",1101);
        }
    }

    private function getWebUrl(string $filePath, string $folder): string {
        $x = $this->urls[$folder].$filePath;
        //$x = 'http://' . $_SERVER['HTTP_HOST'] . $partialUrl . preg_replace($regex, '/\/attach_files\//', $filePath);
        return $x;
        //return '';
    }

    private function storeIn(string $pathName, string $contentB64) {
        try{
            $path = dirname($pathName);
            
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            $file = fopen($pathName, 'wb');
            fwrite($file, base64_decode($contentB64));
            fclose($file);
            return true;
        }catch(Exception $e){
            GZErrorHandler::printError("Error writing file -- $pathName -- $e");
            //throw new Exception("An unexpected error occurred",1111);
            return false;
        }
    }
}