<?php
namespace FileStorage\Event;

use Cake\Event\EventListenerInterface;
use Cake\Event\Event;
use FileStorage\Lib\StorageManager;
use FileStorage\Event\AbstractStorageEventListener;;

/**
 * Local FileStorage Event Listener for the CakePHP FileStorage plugin
 *
 * @author Tomenko Yegeny
 * @license MIT
 */
class LocalFileStorageListener extends AbstractStorageEventListener {

/**
 * List of adapter classes the event listener can work with
 *
 * It is used in FileStorageEventListenerBase::getAdapterClassName to get the
 * class, to detect if an event passed to this listener should be processed or
 * not. Only events with an adapter class present in this array will be
 * processed.
 *
 * @var array
 */
	public $_adapterClasses = array(
		'\Gaufrette\Adapter\Local'
	);

/**
 * Implemented Events
 *
 * @return array
 */
	public function implementedEvents() {
		return [
			'FileStorage.afterSave' => 'afterSave',
			'FileStorage.afterDelete' => 'afterDelete',
		];
	}

/**
 * afterDelete
 *
 * No need to use an adapter here, just delete the whole folder using cakes Folder class
 *
 * @param Event $event
 * @return void
 */
	public function afterDelete(Event $event) {
		if ($this->_checkEvent($event)) {
			$path = Configure::read('FileStorage.basePath') . $event->data['record']['path'];
			if (is_dir($path)) {
				$Folder = new Folder($path);
				return $Folder->delete();
			}
			return false;
		}
	}

/**
 *
 */
	public function buildPath($table, $entity) {
		$path = parent::buildPath($table, $entity);
		// Backward compatibility
		return 'files' . DS . $path;
	}

/**
 * afterSave
 *
 * @param Event $event
 * @return void
 */
	public function afterSave(Event $event) {
		if ($this->_checkEvent($event) && $event->data['record']->isNew()) {
			$table = $event->subject();
			$entity = $event->data['record'];
			$Storage = StorageManager::adapter($entity['adapter']);
			try {
				$filename = $this->buildFileName($table, $entity);
				$entity['path'] = $this->buildPath($table, $entity);
				$Storage->write($entity['path'] . $filename, file_get_contents($entity['file']['tmp_name']), true);
				$table->save($entity, array(
					'validate' => false,
					'callbacks' => false
				));
			} catch (Exception $e) {
				$this->log($e->getMessage(), 'file_storage');
			}
		}
	}
}
