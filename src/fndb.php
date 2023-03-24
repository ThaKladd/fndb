<?php
class fndb {

    private $root = 'db/';
    private $db = '';
    private $folder = '';
    private $cache = [];
    private $do_cache = false;

    public function __construct($dbName = '') {
        $this->setDb($dbName);
    }

    private function setDb($dbName = '') {
        $this->db = $dbName;
        $this->setFolder();
    }

    public function cache($bool = true) {
        $this->do_cache = $bool;
        return $this;
    }

    private function setFolder() {
        if ($this->db) {
            $this->folder = $this->root . $this->db . '/';
            if (!is_dir($this->folder)) {
                mkdir($this->folder, 0777, true);
            }
        }
    }

    public function get($id, $dataMapping = []) {
        $folder = $this->folder . $id;
        if (($this->do_cache && isset($this->cache[$folder])) || is_dir($folder)) {
            $file = ($this->do_cache && isset($this->cache[$folder]) && $this->cache[$folder]) ? $this->cache[$folder] : $this->content($id);
            if ($file) {
                if ($this->do_cache) {
                    $this->cache[$folder] = $file;
                }
                $data = explode('__', rtrim($file, '.db'));
                $return = [
                    'id' => $id,
                    'db' => $this->db,
                    'path' => array_shift($data),
                    'data' => $dataMapping ? array_combine($dataMapping, $data) : $data
                ];
                return $return;
            }
        }
        return false;
    }

    public function delete($id) {
        $file_name = $this->content($id);
        $folder = $this->folder . $id;
        if ($file_name && is_dir($folder)) {
            $files = scandir($folder);
            if (count($files) > 3) {
                $files_deleted = 0;
                foreach ($files as $file) {
                    if (strpos($file, '.db')) {
                        $files_deleted++;
                        unlink($folder . '/' . $file);
                    }
                }
                if ((count($files) - $files_deleted) == 2) {
                    rmdir($folder);
                }
            } else {
                unlink($file_name);
                rmdir($folder);
            }
            if ($this->do_cache && isset($this->cache[$folder])) {
                unset($this->cache[$folder]);
            }
        }
    }

    private function content($id) {
        $folder = $this->folder . $id;
        if ($this->do_cache && isset($this->cache[$folder]) && $this->cache[$folder]) {
            return $folder . '/' . $this->cache[$folder];
        }
        if (is_dir($folder)) {
            foreach (scandir($folder, SCANDIR_SORT_DESCENDING) as $file_name) {
                if (strpos($file_name, '.db')) {
                    return $folder . '/' . $file_name;
                }
            }
        }

        return '';
    }

    /**
     *
     * @param type $id can be 1,2,3,4 or person/1, person/2, or person/job/1, person/job/2
     * @param type $values Array of values that are concatinated with __ for the file or folder name.
     * @param type $content If there is content, then it is stored as a file.
     */
    public function set($id = '', $values = [], $content = '') {
        $folder = $this->folder . $id;
        if ($values) {
            $dbFile = '__' . implode('__', $values) . '.db';
            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }
            if ($this->isFileable($dbFile)) {
                $fp = fopen($folder . '/' . $dbFile, 'w');
                if ($this->do_cache) {
                    $this->cache[$folder] = $dbFile;
                }
                if ($content) {
                    fwrite($fp, $content);
                }
                fclose($fp);
            }
        }
    }

    function isFileable($string = '') {
        if (preg_match('/^[\p{L}0-9_\-\.\~\ \/]+$/', $string)) {
            if (strlen($string) < 250) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
        //$regex = @"^[\w\-. ]+$"; //0-9a-zA-Z_-. and space
        //\A(?!(?:COM[0-9]|CON|LPT[0-9]|NUL|PRN|AUX|com[0-9]|con|lpt[0-9]|nul|prn|aux)|\s|[\.]{2,})[^\\\/:*"?<>|]{1,254}(?<![\s\.])\z
        //^[\p{L}0-9_\-.~]+$ - full unicode
    }


    /**
     * Extra function for recursevly saving array space by ordering array in a better way
     * @param array $resulting_array
     * @param type $array
     * @return array
     */
    private function run_array(&$resulting_array, $array = null) {
        if ($array) {
            $key = array_shift($array);
            if (!isset($resulting_array[$key])) {
                $resulting_array[$key] = [];
                $this->run_array($resulting_array[$key], $array);
            } else {
                $this->run_array($resulting_array[$key], $array);
            }
        }
        return $resulting_array;
    }

}
