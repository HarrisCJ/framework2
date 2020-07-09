<?php
/**
 * Contains the definition of Formdata GET support class
 *
 * @author Lindsay Marshall <lindsay.marshall@ncl.ac.uk>
 * @copyright 2020 Newcastle University
 */
    namespace Framework\FormData;

/**
 * A class that provides helpers for accessing GET form data
 */
    class Get extends AccessBase
    {
        public function _construct()
        {
            parent::__construct(INPUT_GET);
        }
    }
?>