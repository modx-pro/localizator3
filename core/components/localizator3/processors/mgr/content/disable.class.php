<?php

require_once(dirname(__FILE__) . '/update.class.php');

class localizatorContentDisableProcessor extends localizatorContentUpdateProcessor
{
    /**
     * @return bool
     */
    public function beforeSet()
    {
        $this->setProperty('active', 0);

        return true;
    }
}

return 'localizatorContentDisableProcessor';
