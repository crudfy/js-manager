<?php

namespace Crudfy\JsManager;


use Exception;

class Directory
{

    private $filepath;

    private $fileExt ;

    private $data = [];
    private $dataTree = [];

    /**
     * Create a new FileManager Instance
     * @param string $filepath Phrase to return
     */
    public function __construct($path, $fileExt = '.js')
    {
        $this->fileExt = $fileExt;
        $this->path = $path;
        $data = [];
        $dataTree = [];

        list('data' => $data, 'dataTree' => $dataTree) = $this->recursiveData($path, $data, $dataTree);
        $this->data = $data;
        $this->dataTree = $dataTree;
    }

    private function recursiveData($path, &$data, &$dataTree) {
        $files = scandir($path);
        foreach ($files as $key => $fileOrFold) {
            if (!in_array($fileOrFold, ['.', '..'])) {
                $_path = $path.'/'.$fileOrFold;
                if(is_dir($_path)) {
                    $dataTree[$fileOrFold] = [
                        'type' => 'fold',
                        'path' => $_path,
                        'files' => []
                    ];
                    $data["{$fileOrFold}__fold"] = [];
                    $this->recursiveData($_path, $data["{$fileOrFold}__fold"], $dataTree[$fileOrFold]['files']);
                } else if (is_file($_path)) {
                    $name = explode('.', $fileOrFold)[0];
                    $fileData = (new File($_path))->getData();
                    $dataTree[$name] = [
                        'type' => 'file',
                        'path' => $_path,
                        'data' => $fileData
                    ];
                    $data[$name.'__file'] = $fileData;
                }
            }
        }
        return ['data' => $data, 'dataTree' => $dataTree];
    }

    /**
     * Create a new FileManager Instance
     * @return array $data  Phrase to return
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Create a new FileManager Instance
     * @param string $data Data to converter in json data
     * @param string $filepath New filepath
     */
    public function save($data) {
        $path = $this->path;
        $oldData = $this->data;
        $this->recursiveSaveFiles($data, $path, $oldData);
    }

    private function recursiveSaveFiles($data, $path, $oldData = []) {
        foreach ($data as $fileOrFold => $d) {
            $oldD = !empty($oldData[$fileOrFold]) ? $oldData[$fileOrFold] : [];
            if (strpos($fileOrFold, '__fold') !== false) {
                $this->recursiveSaveFiles(
                    $d,
                    $path.'/'.str_replace('__fold', '', $fileOrFold),
                    $oldD
                );
            }
            else if (strpos($fileOrFold, '__file') !== false) {
                $_path = $path.'/'.str_replace('__file', '', $fileOrFold).$this->fileExt;
                if ($d !== $oldD) {
                    File::saveFileData($_path, $d);
                }

            }
        }
    }
}
