<?php
App::uses('AppController', 'Controller');

function array_flatten($arr) {
    $result = array();
    foreach ($arr as $item) {
        if (is_array($item)) {
            $result = array_merge($result, array_flatten($item));
        } else {
            $result[] = $item;
        }
    }
    return $result;
}

class LevelsController extends AppController {
    public $components = array('Search.Prg');
    public $presetVars = true;

    public $helpers = array('Html', 'Form', 'Paginator');
    public $uses = array('Level', 'User', 'Rating', 'Comment');

    public $paginate = array(
            'limit' => 24,
            'order' => array(
                    'Level.name' => 'asc'
            )
    );

    function _checkFile($field) {
        if(!isset($this->request->data['Level'][$field.'File'])) {
            return false;
        }

        $arr = $this->request->data['Level'][$field.'File'];

        if ((isset($arr['error']) && $arr['error'] == 0) ||
                (!empty( $arr['tmp_name']) && $arr['tmp_name'] != 'none')
        ) {
            if(is_uploaded_file($arr['tmp_name'])) {
                $handle = fopen($arr['tmp_name'], 'r');
                $content = fread($handle, $arr['size']);
                fclose($handle);
                $this->request->data['Level'][$field] = $content;
                return true;
            }
        }
        return false;
    }

    // gets a level by id and returns appropriate errors
    function _getLevel($id) {
        if($id == null) {
            throw new BadRequestException('You must specify a level');
        }

        if(!is_numeric($id)) {
            $level = $this->Level->findByLevelFilename($id . '.level');
        } else {
            $level = $this->Level->findById($id);
        }

        if(empty($level)) {
            throw new BadRequestException('Level not found');
        }

        return $level;
    }

    /**
     * Attempts to read the uploaded screenshot (as a PNG image), then resizes it and
     * creates a thumbnail as needed.
     */
    function _getScreenshot() {
        if ($this->_isValidUpload('screenshot')) {
            $arr = $this->request->data['Level']['screenshot'];

            $imageInfo = @getimagesize($arr['tmp_name']);
            if (!$imageInfo || $imageInfo[2] !== IMAGETYPE_PNG) {
                return false;
            }

            $newFileName = uniqid('screenshot_', true) . '.png';
            $newPath = APP . 'webroot' . DS . 'img' . DS . $newFileName;
            $newThumbnailPath = APP . 'webroot' . DS . 'img' . DS . 't' .  $newFileName;

            $source = @imagecreatefrompng($arr['tmp_name']);
            if(!$source) {
                return false;
            }
            $sourceWidth = imagesx($source);
            $sourceHeight = imagesy($source);

            // resize image
            $resizeRatio = max(1, $sourceWidth / 800, $sourceHeight / 600);
            $destWidth = (int)($sourceWidth / $resizeRatio);
            $destHeight = (int)($sourceHeight / $resizeRatio);

            $dest = imagecreatetruecolor($destWidth, $destHeight);
            imagecopyresampled(
                    $dest, $source,
                    0, 0,
                    0, 0,
                    $destWidth, $destHeight,
                    $sourceWidth, $sourceHeight
            );

            imagepng($dest, $newPath);
            imagedestroy($dest);

            // create thumbnail
            $resizeRatio = max(1, $sourceWidth / 200, $sourceHeight / 150);
            $thumbWidth = (int)($sourceWidth / $resizeRatio);
            $thumbHeight = (int)($sourceHeight / $resizeRatio);

            $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
            imagecopyresampled(
                    $thumb, $source,
                    0, 0,
                    0, 0,
                    $thumbWidth, $thumbHeight,
                    $sourceWidth, $sourceHeight
            );

            imagepng($thumb, $newThumbnailPath);
            imagedestroy($thumb);
            imagedestroy($source);
            $this->request->data['Level']['screenshot_filename'] = $newFileName;
            return $newFileName;
        }
        return false;
    }

    /**
     * Checks that the file uploaded as Level.$field exists and
     * is actually an uploaded file.
     */
    function _isValidUpload($field) {
        if (!isset($this->request->data['Level'][$field])) {
            return false;
        }
        $arr = $this->request->data['Level'][$field];
        return is_array($arr)
               && isset($arr['error'])
               && $arr['error'] === UPLOAD_ERR_OK
               && !empty($arr['tmp_name'])
               && $arr['tmp_name'] !== 'none'
               && is_uploaded_file($arr['tmp_name']);
    }

    function _performUpload() {
        if(!$this->Auth->loggedIn()) {
            if(!$this->Auth->login()) {
                throw new ForbiddenException('You must be logged in');
            }
        }

        if(isset($this->request->data['Level']['screenshot'])) {
            $this->_getScreenshot();
        }

        if(!isset($this->request->data['Level']['content'])) {
            throw new BadRequestException('No level content found');
        }

        $id = null;
        $matches = array();
        preg_match('/LevelDatabaseId\s+([^\r\n]+)/', $this->request->data['Level']['content'], $matches);

        if(count($matches) > 1) {
            $id = trim($matches[1]);
        }

        $level = false;
        if($id !== null) {
            try {
                $level = $this->_getLevel($id);
            } catch (Exception $e) {
            }
        }

        if($level) {
            if($level['Level']['user_id'] != $this->Auth->user('user_id')) {
                throw new ForbiddenException('You can only update a level you uploaded');
            }
            $this->request->data['Level']['id'] = $level['Level']['id'];
        } else {
            $this->Level->create();
        }

        $this->request->data['Level']['user_id'] = $this->Auth->user('user_id');
        return $this->Level->save($this->request->data);
    }

    public function beforeFilter() {
        parent::beforeFilter();
        $this->Auth->deny();
        $this->Auth->allow('upload', 'download', 'raw', 'index', 'view', 'search', 'rate', 'all');

        if($this->Auth->loggedIn()) {
            $this->Auth->allow('edit', 'add', 'massupload');
        }
    }

    public function index() {
        $last = $this->Level->last();
        $data = Cache::read("index_lists_$last");
        
        if(!$data)
        {
            $fields = array(
                    'Level.id',
                    'Level.name',
                    'Level.rating',
                    'Level.game_type',
                    'Level.screenshot_filename',
                    'Level.user_id',
                    'Level.downloads',
                    'Level.team_count',
                    'Level.comment_count',
                    'Level.author',
                    'Level.last_updated'
            );

            $data = array(
                    'Recently Updated' => $this->Level->find('all', array(
                            'recursive' => 1,
                            'fields' => $fields,
                            'order' => 'Level.last_updated DESC',
                            'limit' => 8
                    )),
                    'Highest Rated' => $this->Level->find('all', array(
                            'fields' => $fields,
                            'order' => 'Level.last_updated DESC',
                            'order' => 'Level.rating DESC',
                            'recursive' => 1,
                            'limit' => 8
                    )),
                    'Most Downloaded' => $this->Level->find('all', array(
                            'fields' => $fields,
                            'order' => 'Level.last_updated DESC',
                            'order' => 'Level.downloads DESC',
                            'recursive' => 1,
                            'limit' => 8
                    )),
                    'Random' => $this->Level->find('all', array(
                            'fields' => $fields,
                            'order' => 'Level.last_updated DESC',
                            'order' => 'RAND()',
                            'recursive' => 1,
                            'limit' => 8
                    )),
            );
            Cache::write("index_lists_$last", $data);
        }

        $this->set('levelLists', $data);
    }

    public function all() {
        $this->set('levels', $this->paginate());
    }

    public function edit($id = null) {
        $level = $this->_getLevel($id);

        if(!$this->Auth->loggedIn()) {
            throw new ForbiddenException('You must be logged in to edit a level');
        }

        if(!$this->isAdmin() && $level['Level']['user_id'] != $this->Auth->user('user_id')) {
            throw new ForbiddenException('You can only edit a level you uploaded');
        }

        if($this->request->is('post') || $this->request->is('put')) {

            $this->_checkFile('content');
            $this->_checkFile('levelgen');

            if(!empty($this->request->data['Level']['screenshot']['tmp_name'])) {
                if(!$this->_getScreenshot()) {
                    throw new BadRequestException('Unable to read screenshot file. You must upload a .png image');
                }
            }

            $this->Level->id = $level['Level']['id'];
            $result = $this->Level->save($this->request->data);
            if($result) {
                $this->Session->setFlash('Level updated');
                return $this->redirect(array('action' => 'view', $id));
            } else {
                $valErrors = array_shift($this->Level->validationErrors);
                $theError = strlen($valErrors[0]) <= 1 ? $valErrors : $valErrors[0];
                $this->Session->setFlash('Could not save level: ' . $theError);
            }
        }

        if(empty($this->request->data)) {
            $this->request->data = $level;
        }
        $tags = $this->Level->Tag->find('list');
        $this->set(compact('tags'));
    }

    public function view($id) {
        $last = $this->Level->last($id);
        $data = Cache::read("level_view_{$id}_$last");
        if(!$data)
        {
            $level = $this->Level->findById($id);

            if(!$level)
                throw new NotFoundException('The specified level does not exist');

            $current_user_rating = $this->Rating->findByUserIdAndLevelId($this->Auth->user('user_id'), $id);
            $comments = $this->Comment->findAllByLevelId($id);
            $data = compact('level', 'current_user_rating', 'comments');
            Cache::write("level_view_{$id}_$last", $data);
        }

        $this->set('logged_in', $this->Auth->loggedIn());
        $this->set('is_owner', intval($data['level']['Level']['user_id']) == intval($this->Auth->user('user_id')));
        $this->set($data);
    }

    public function rate($id, $value) {
        if(!$this->Auth->loggedIn() && !$this->Auth->login()) {
            throw new ForbiddenException('You must be logged in');
        }

        $this->Level->id = $id;
        if($this->Level->rate($this->Auth->user('user_id'), $value)) {
            if($this->layout == 'client') {
                $level = $this->Level->findById($id);
                $this->Session->setFlash('New rating for ' . $level['Level']['name'] . ' by ' . $level['Level']['author'] . ': ' . $level['Level']['rating']);
                return;
            }
        } else {
            $err = array_shift($this->Level->validationErrors);
            $this->Session->setFlash($err);
            throw new BadRequestException($err);
        }
        return $this->redirect($this->referer());
    }

    public function add() {
        if(!$this->Auth->loggedIn() && !$this->Auth->login()) {
            throw new ForbiddenException('You must be logged in to upload a level');
        }

        if($this->request->is('post')) {
            $this->Level->create();
            $this->Level->set('user_id', $this->Auth->user('user_id'));

            $this->_checkFile('content');
            $this->_checkFile('levelgen');

            if(isset($this->request->data['Level']['screenshot'])) {
                $this->_getScreenshot();
            }

            if($this->Level->save($this->request->data)) {
                $this->Session->setFlash('Your post has been saved.');
                return $this->redirect(array('action' => 'index'));
            } else {
		$valErrors = array_shift($this->Level->validationErrors);
                $theError = strlen($valErrors[0]) <= 1 ? $valErrors : $valErrors[0];
                $this->Session->setFlash('Could not save level: ' . $theError);
            }
        } else {
            $tags = $this->Level->Tag->find('list');
            $this->set(compact('tags'));
        }
    }

    public function raw($id = null, $type = 'content', $uncounted = false) {
        $level = $this->_getLevel($id);

        if($type !== 'content' && $type !== 'levelgen') {
            throw new BadRequestException('Valid display modes are "level" and "levelgen"');
        }

        $responseBody = $level['Level'][$type];
        if($type == 'levelgen' && !empty($level['Level']['levelgen_filename'])) {
            $responseBody = "-- " . $level['Level']['levelgen_filename'] . "\r\n" . $responseBody;
        }

        if($type != 'levelgen' && !$uncounted) {
            $this->Level->id = $level['Level']['id'];
            $this->Level->saveField('downloads', $level['Level']['downloads'] + 1);
        }

        $this->response->type('text/text');
        $this->response->body($responseBody);
        return $this->response;
    }

    public function download($id = null) {
        $level = $this->_getLevel($id);
        $this->Level->id = $level['Level']['id'];
        $this->Level->saveField('downloads', $level['Level']['downloads'] + 1);

        $levelName = $level['Level']['level_filename'];

        $tmp = tempnam(sys_get_temp_dir(), 'levelzip_');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            if (file_exists($tmp)) {
                @unlink($tmp);
            }
            throw new InternalErrorException('Could not create zip archive');
        }
        $zip->addFromString($levelName, $level['Level']['content']);

        if(!empty($level['Level']['levelgen'])) {
            $zip->addFromString($level['Level']['levelgen_filename'], $level['Level']['levelgen']);
        }

        $zip->close();

        register_shutdown_function(function() use ($tmp) {
            if (file_exists($tmp)) {
                @unlink($tmp);
            }
        });

        $filename = preg_replace('/\.level$/', '', $levelName) . '.zip';
        $this->response->file($tmp, array('download' => true, 'name' => $filename));
        return $this->response;
    }

    // client unified write interface: updates or creates as needed
    public function upload() {
        if(!$this->_performUpload()) {
            $this->response->statusCode(403);
            $this->response->body(array_shift($this->Level->validationErrors));
            return $this->response;
        }

        $this->response->body($this->Level->getId());
        return $this->response;
    }

    public function delete($id) {
        $level = $this->_getLevel($id);

        if(!$this->Auth->loggedIn() || intval($level['Level']['user_id']) != intval($this->Auth->user('user_id')) && !$this->isAdmin()) {
            throw new ForbiddenException('You can only delete a level you uploaded');
        }

        $this->Level->delete($id);
        $this->redirect('index');
    }

    public function search() {
        $this->Prg->commonProcess();
        $this->paginate['conditions'] = $this->Level->parseCriteria($this->passedArgs);
        $this->set('levels', $this->paginate());
        $tags = $this->Level->Tag->find('list');
        $this->set(compact('tags'));
    }

    public function massupload() {
        if($this->request->is('post')) {
            if(!$this->_isValidUpload('zipFile')) {
                throw new BadRequestException('You must upload a .zip file');
            }

            $filename = $this->request->data['Level']['zipFile']['tmp_name'];

            $zip = new ZipArchive();
            if ($zip->open($filename) !== true) {
                throw new BadRequestException('Invalid .zip file');
            }

            $result = array();

            $this->request->data = array();
            for($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                $entryFilename = $entry['name'];

                // skip directories, which have a trailing slash in their names
                if(substr($entryFilename, strlen($entryFilename) - 1) === '/')
                    continue;

                $entryContents = $zip->getFromIndex($i);

                // information about the upload
                $info = array(
                        'errors' => array(),
                        'warnings' => array(),
                        'name' => null,
                        'id' => null,
                        'filename' => $entryFilename,
                        'levelgen_filename' => null
                );

                // skip files that don't end in .level
                if(!preg_match('/\.level$/', $entryFilename))
                    continue;

                // warn when an otherwise valid file is contained in the __MACOSX directory
                if(strstr($entryFilename, '__MACOSX') !== false) {
                    array_push($info['warnings'], "Ignoring file contained in __MACOSX directory: '$entryFilename'");
                } else {
                    $this->request->data['Level'] = array();
                    $this->request->data['Level']['content'] = $entryContents;

                    // find levelgen if needed
                    $matches = array();
                    if(preg_match('/Script +([^ \n]+)/', $entryContents, $matches) && sizeof($matches) > 1 && !empty($matches[1])) {
                        // we only look in the current directory for levelgen files, so we'll build our search path
                        $dir_parts = explode('/', $entryFilename);
                        array_pop($dir_parts);
                        $dir = implode('/', $dir_parts);
                        $levelgenFilename = trim(preg_replace('/\.levelgen$/', '', $matches[1]));
                        $target = !empty($dir) ? $dir . '/' . $levelgenFilename : $levelgenFilename;

                        // the client checks for $file.levelgen and then for $file, so we'll do the same
                        $levelgenContents = $zip->getFromName($target . '.levelgen');
                        if($levelgenContents === false)
                            $levelgenContents = $zip->getFromName($target);

                        // report an error if we can't find the levelgen file
                        if($levelgenContents === false) {
                            array_push($info['errors'], "Could not find specified levelgen file '$levelgenFilename' in archive");
                        }

                        $this->request->data['Level']['levelgen'] = $levelgenContents;
                    }

                    $this->Level->validationErrors = array();
                    $level = $this->_performUpload();
                    if(!$level) {
                        $info['errors'] = array_merge($info['errors'], array_flatten($this->Level->validationErrors));
                    } else {
                        $info['name'] = $level['Level']['name'];
                        $info['id'] = $level['Level']['id'];
                    }
                }

                array_push($result, $info);
            }

            $zip->close();

            $this->set('uploads', $result);
        }
    }
}
?>
