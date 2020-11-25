<?php
/**
 * A model class for the RedBean object Upload
 *
 * This is a Framework system class - do not edit!
 *
 * @author Lindsay Marshall <lindsay.marshall@ncl.ac.uk>
 * @copyright 2015-2020 Newcastle University
 * @package Framework
 * @subpackage SystemModel
 */
    namespace Model;

    use \Support\Context;
/**
 * Upload table stores info about files that have been uploaded...
 * @psalm-suppress UnusedClass
 */
    class Upload extends \RedBeanPHP\SimpleModel
    {
        use \ModelExtend\Upload;
/**
 * Return the owner of this uplaod
 *
 * @return ?object
 */
        public function owner() : ?object
        {
            return $this->bean->user;
        }
/**
 * Store a file
 *
 * This is the basic functionality assumed by the framework. You can adapt this by changing this function.
 * Best though if you only add functionality :-)
 *
 * @param Context   $context    The context object for the site
 * @param array     $da         The relevant $_FILES element (or similar generated by FormData)
 * @param bool      $public     If TRUE then store in the public directory
 * @param ?object   $owner      The user who owns the upload. If NULL then  the currently logged in user
 * @param int       $index      If there is an array of files possibly with other data, then this is the index in the array.
 *
 * @throws \Framework\Exception\InternalError
 * @return bool
 */
        public function savefile(Context $context, array $da, bool $public, $owner = NULL, $index = 0) : bool
        {
            if ($da['size'] == 0 || $da['error'] != UPLOAD_ERR_OK)
            { // 0 length file or there was an error so ignore
                return FALSE;
            }
            if (!$public && !is_object($owner))
            {
                if (!$context->hasuser())
                { // no logged in user! This should never happen...
                    throw new \Framework\Exception\InternalError('No user');
                }
                $owner = $context->user();
            }
            [$dir, $pname, $fname] = $this->mkpath($context, $owner, $public, $da);
            if (!@move_uploaded_file($da['tmp_name'], $fname))
            {
                @chdir($dir);
                throw new \Framework\Exception\InternalError('Cannot move uploaded file to '.$fname);
            }
            $this->bean->added = $context->utcnow();
            $pname[] = $fname;
            $this->bean->fname = DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $pname);
            $this->bean->filename = $da['name'];
            $this->bean->public = $public ? 1 : 0;
            $this->bean->user = $owner;
            $this->addData($context, $index); // call the user extend function in the trait
            \R::store($this->bean);
            if (!@chdir($dir))
            { // go back to where we were in the file system
                throw new \Framework\Exception\InternalError('Cannot chdir to '.$dir);
            }
            return TRUE;
        }
/**
 * Replace the existing uploaded file with another one
 *
 * @param \Support\Context    $context
 * @param array               $da        The file upload info array via FormData
 * @param int                 $index     The index if this all part of an array of data
 *
 * @throws \Framework\Exception\InternalError
 *
 * @return void
 */
        public function replace(Context $context, array $da, int $index = 0) : void
        {
            $oldfile = $this->bean->fname;
            [$dir, $pname, $fname] = $this->mkpath($context, $this->bean->user, $this->bean->public, $da);
            if (!@move_uploaded_file($da['tmp_name'], $fname))
            {
                @chdir($dir);
                throw new \Framework\Exception\InternalError('Cannot move uploaded file to '.$fname);
            }
            $this->bean->added = $context->utcnow();
            $pname[] = $fname;
            $this->bean->fname = DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $pname);
            $this->bean->filename = $da['name'];
            $this->updateData($context, $index); // call the user extend function in the trait
            \R::store($this->bean);
            unlink($context->local()->basedir().$oldfile);
            if (!@chdir($dir))
            { // go back to where we were in the file system
                throw new \Framework\Exception\InternalError('Cannot chdir to '.$dir);
            }
        }
/**
 * Make a path for a new file
 *
 * @param \Support\Context $context
 * @param ?object           $owner
 * @param bool             $public
 * @param array            $da
 *
 * @return array
 */
        private function mkpath(\Support\Context $context, ?object $owner, bool $public, array $da) : array
        {
            $dir = getcwd();
            chdir($public ? $context->local()->assetsdir() : $context->local()->basedir());
            $pname = [$public ? 'public' : 'private', is_object($owner) ? $owner->getID() : '0', date('Y'), date('m')];
            foreach ($pname as $pd)
            { // walk the path cding and making if needed
                $this->mkch($pd);
            }
            return [$dir, $pname, uniqid('', TRUE).'.'.pathinfo($da['name'], PATHINFO_EXTENSION)];
        }
/**
 * Make a directory if necessary and cd into it
 *
 * @param string    $dir The directory name
 *
 * @throws \Framework\Exception\Forbidden
 *
 * @return void
 */
        private static function mkch(string $dir) : void
        {
            if (!file_exists($dir))
            {
                if (!@mkdir($dir, 0770))
                {
                    throw new \Framework\Exception\Forbidden('Cannot mkdir '.$dir);
                }
            }
            if (!@chdir($dir))
            {
                throw new \Framework\Exception\Forbidden('Cannot chdir '.$dir);
            }
        }
    }
?>