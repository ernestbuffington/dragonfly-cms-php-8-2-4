<?php
/*
	Dragonfly™ CMS, Copyright © since 2010
	https://dragonfly.coders.exchange

	Dragonfly CMS is released under the terms and conditions
	of the GNU GPL version 2 or any later version
*/

namespace Poodle;

// On destruct Fatal error:  Class 'Poodle\Events\Event' not found
class_exists('Poodle\\Events\\Event');

trait Events
{
	protected
		$_poodle_events = array();

	protected function loadEventListeners()
	{
		$SQL = \Dragonfly::getKernel()->SQL;
		$qr = $SQL->query("SELECT class_event, class_callable FROM {$SQL->TBL->classes_events}
			WHERE class_name = ".$SQL->quote(strtolower(static::class)));
		while ($r = $qr->fetch_row()) {
			if (is_callable($r[1])) {
				$this->addEventListener($r[0], $r[1]);
			}
		}
	}

	public static function hookEventListener($type, callable $function)
	{
		\Dragonfly::getKernel()->SQL->TBL->classes_events->insert(array(
			'class_name' => strtolower(static::class),
			'class_event' => strtolower($type),
			'class_callable' => strtolower($function)
		));
	}

	public static function unhookEventListener($type, callable $function)
	{
		\Dragonfly::getKernel()->SQL->TBL->classes_events->delete(array(
			'class_name' => strtolower(static::class),
			'class_event' => strtolower($type),
			'class_callable' => strtolower($function)
		));
	}

	public function addEventListener($type, callable $function)
	{
		$type = strtolower($type);
		if (is_array($function)) {
			$function = is_object($function[0]) ? $function : "{$function[0]}::{$function[1]}";
		}
		if (!is_callable($function)) {
			throw new \Exception("Function for Event '{$type}' is not callable");
		}
		if (!isset($this->_poodle_events[$type])) {
			$this->_poodle_events[$type] = array($function);
		}
		else if (!in_array($function, $this->_poodle_events[$type], true)) {
			$this->_poodle_events[$type][] = $function;
		}
	}

	public function dispatchEvent(\Poodle\Events\Event $event)
	{
		$type = strtolower($event->type);
		if (!empty($this->_poodle_events[$type])) {
			$event->target = $this;
			// Execute in reverse order?
			foreach ($this->_poodle_events[$type] as $callback) {
//				try {
				is_array($callback) ? $callback[0]->{$callback[1]}($event) : call_user_func($callback, $event);
//				}
//				catch (\Throwable $e) { \Poodle\LOG::error($event, $e->getMessage()."\n".$e->getTraceAsString()); }
//				catch (\Exception $e) { \Poodle\LOG::error($event, $e->getMessage()."\n".$e->getTraceAsString()); }
			}
		}
	}

	public function removeEventListener($type, callable $function)
	{
		$type = strtolower($type);
		if (!empty($this->_poodle_events[$type])) {
			$key = array_search($function, $this->_poodle_events[$type], true);
			if (false !== $key) { unset($this->_poodle_events[$type][$key]); }
		}
	}

	public function triggerEvent($type)
	{
		$type = strtolower($type);
		if (!empty($this->_poodle_events[$type])) {
			$this->dispatchEvent(new \Poodle\Events\Event($type));
		}
	}

	protected function removeAllEventListeners($type=null)
	{
		if ($type) unset($this->_poodle_events[strtolower($type)]);
		else $this->_poodle_events = array();
	}
}
