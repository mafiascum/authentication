<?php

namespace mafiascum\authentication\migrations;

class cron extends \phpbb\db\migration\migration
{
   public function effectively_installed()
   {
      return isset($this->config['mafiascum_authentication_autoRemove_gc']);
   }

   static public function depends_on()
   {
      return array('\phpbb\db\migration\data\v310\dev');
   }

   public function update_data()
   {
      return array(
         array('config.add', array('mafiascum_authentication_autoRemove_last_gc', 0)), // last run
         array('config.add', array('mafiascum_authentication_autoRemove_gc', (60 * 60))), // seconds between run; 1 hour
      );
   }
}
?>