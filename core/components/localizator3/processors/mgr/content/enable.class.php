<?php

require_once(dirname(__FILE__) . '/update.class.php');

class localizatorContentEnableProcessor extends localizatorContentUpdateProcessor
{
    /**
     * @return bool
     */
    public function beforeSet()
    {
        $this->setProperty('active', 1);

        return true;
    }
}

return 'localizatorContentEnableProcessor';
