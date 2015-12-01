<?php
namespace Craft;

class SproutForms_OnePageCrmPlugin extends BasePlugin
{
  function getName()
  {
    return Craft::t('Forms - OnePageCRM Integration');
  }

  function getVersion()
  {
    return '0.1';
  }

  function getDeveloper()
  {
    return 'Moby, Inc.';
  }

  function getDeveloperUrl()
  {
    return 'http://mobyinc.com';
  }

  public function init()
  {
    craft()->on('sproutForms.onSaveEntry', function($event)
    {
      try {
        if ($event->params['isNewEntry']) {
          craft()->sproutForms_onePageCrm_contacts->saveContactWithEntry($event->params['entry']);
        }
      } catch (Exception $e) {
        SproutForms_OnePageCrmPlugin::log('error:' . $e->getMessage());
      }
    });
  }
}