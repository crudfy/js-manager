<?php

namespace Crudfy\JsManager;


use Exception;

class File
{

    private $filepath;

    private $data = [];

    /**
     * Create a new FileManager Instance
     * @param string $filepath Phrase to return
     */
    public function __construct($filepath)
    {
        $this->filepath = $filepath;
        if (!is_file($filepath)) {
            throw new Exception("File not found");
        }
        $this->data = self::readData($filepath);
    }

    public static function readData($filepath) {
        $content = file_get_contents($filepath);
        $content = trim(str_replace(["export default", "\n"], "", $content));
        $content = preg_replace('/\s\s+/', '',$content);
        $content = str_replace(['};', '} ;'], '}', $content);
        $content = str_replace('return ', 'return__space__', $content);
        $content = str_replace(': true', ':"__true__"', $content);
        $content = str_replace(': false', ':"__false__"', $content);
        $content = str_replace([': null', ':null'], ':""', $content);
        foreach(preg_split("#[{,:]#", $content) as $str) {
            $str = trim($str);
            $replace = str_replace(' ', '__space__', $str);
            $content = str_replace($str, $replace, $content);
        }
        $content = str_replace(" ", "", $content);
        $content = str_replace(':`', ':"__backtick__', $content);
        $content = str_replace('(`', '("__backtick__', $content);
        $content = str_replace('`', '__backtick__"', $content);
        $content = str_replace("},}", "}}", $content);
        $content = str_replace("},]", "}]", $content);
        $content = str_replace("\\'", '__ap__', $content);
        $content = str_replace("'", '"', $content);
        $content = str_replace(",}", "}", $content);
        $content = str_replace('__space__=>{', '=>{', $content);
        $content = preg_replace_callback('#[a-z0-9]{1,}(:){1}[a-z]{1,}#i', function ($args) {
            return str_replace(':', '__type__', $args[0]);
        }, $content);

        $content = preg_replace_callback('#[{,]{1}[a-z0-9]{1,}(:){1}(\(){1}#', function ($args) {
            $arg = $args[0];
            $func = '';
            $startChar = '';
            if (strpos($arg, ',') === 0) {
                $startChar = ',';
                $func = explode(':', array_reverse(explode(',', $arg))[0])[0];
            } else if (strpos($arg, '{') === 0){
                $startChar = '{';
                $func = explode(':', array_reverse(explode('{', $arg))[0])[0];
            }
            return $startChar.'"__func__'.$func.'__func__":"__func__(';
        }, $content);


        $contentParts = explode('=>{', $content);
        foreach ($contentParts as $i => $str) {
            if (preg_match('#(\()(.)*(\)){1}$#', $str)) {
                $nextPart = $contentParts[$i+1];
                $parts = str_split( $nextPart);
                $funcEndPos = null;
                $level = 1;
                $okPosition = null;
                foreach ($parts as $position => $part) {
                    if ($part === '}') {
                        $level --;
                        if ($level === 0 && $funcEndPos === null) {
                            $funcEndPos = $position;
                            if ($okPosition === null) {
                                $okPosition = $position;
                            }
                        }
                    }
                    if ($part === '{') {
                        $level ++;
                        $funcEndPos = null;
                    }
                }
                $funcEndPos = $okPosition +1;
                $funcBody = substr($nextPart, 0, $funcEndPos);
                $funcBodyReplace = str_replace('"', '\"', $funcBody);
                $funcBodyReplace = str_replace(',', '__vr__', $funcBodyReplace);
                $funcBodyReplace .= '__func__"';
                $funcBodyReplace = str_replace(',', '__vr__', $funcBodyReplace);
                $_nextPart = $funcBodyReplace.substr($nextPart, $funcEndPos);
                $contentParts[$i+1]= $_nextPart;
            }
        }
        $content = implode('=>{', $contentParts);

        $content = preg_replace_callback('#[{,]{1}[a-z0-9]{1,}(:){1}#', function ($args) {
            $arg = $args[0];
            $key = '';
            if (strpos($arg, ',') === 0) {
                $key = explode(':',array_reverse(explode(',', $arg))[0])[0];
            } else if (strpos($arg, '{') === 0){
                $key = explode(':',array_reverse(explode('{', $arg))[0])[0];
            }
            return str_replace($key, '"'.$key.'"', $arg);
        }, $content);
        $content = str_replace('"__space__}', '"}', $content);
        $content = str_replace('}];', '}]', $content);

        $content = str_replace('__space__', ' ', $content);
        $content = str_replace('__type__', ':', $content);
        $content = str_replace("];", "]", $content);

        $data = json_decode($content, true);
        if ($data === null) {
            throw new Exception("Data format is not ok | ".$content);
        }

        return $data;
    }

    /**
     * Create a new FileManager Instance
     * @return array $data  Phrase to return
     */
    public function getData() {
        return $this->data;
    }

    public static function saveFileData($path, $data) {
        if (is_file($path)) {
            if (self::readData($path) === $data) {
                return null;
            }
        }
        $content = json_encode($data);
        $content = str_replace('__space__', ' ', $content);
        $content = str_replace('__type__', ': ', $content);
        $content = str_replace('__func__",', ',', $content);
        $content = str_replace('__func__"}', '}', $content);
        $content = str_replace(['"__func__', '__func__"'], '', $content);
        $content = str_replace(['"_backtick', '_backtick"'], '`', $content);
        $content = str_replace('"__space__}', '"}', $content);
        $content = str_replace('}];', '}]', $content);
        $content = str_replace('__space__', ' ', $content);
        $content = str_replace(':"__null__"', ': ""', $content);
        $content = str_replace("];", "]", $content);
        $content = trim($content);
        $content = 'export default '.$content;
        $nbCharts = file_put_contents($path, $content);
//        $comando = "prettier $path --write > null";
//        exec($comando);
        $dir = __DIR__;
        $cmd = "php -q {$dir}\\format.php {$path}";
        $call =  function() use($cmd) {
            if (substr(php_uname(), 0, 7) == "Windows"){
                pclose(popen("start /B ". $cmd, "r"));
            }
            else {
                exec($cmd . " > /dev/null &");
            }
        };

        $call();

//        return function () use ($comando){
//            if(!$comando){
//                throw new Exception("No command given");
//            }
//            // If windows, else
//            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
//                system($comando." > NUL");
//            }else{
//                shell_exec("/usr/bin/nohup ".$comando." >/dev/null 2>&1 &");
//            }
//        };
//        return function () use ($path) {
//            $cmd = "prettier $path --write";
//            if (substr(php_uname(), 0, 7) == "Windows"){
//                pclose(popen("start /B ". $cmd, "r"));
//            }
//            else {
//                exec($cmd . " > /dev/null &");
//            }
//        };
    }

    /**
     * Create a new FileManager Instance
     * @param string $data Data to converter in json data
     * @param string $filepath New filepath
     */
    public function save($data, $filepath = null) {
        $path = $this->filepath;
        if ($filepath) {
            $path = $filepath;
        }

        return self::saveFileData($path, $data);
    }
}
