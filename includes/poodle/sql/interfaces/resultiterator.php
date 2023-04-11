<?php
/*	Dragonfly™ CMS, Copyright © since 2010 by CPG-Nuke Dev Team. All rights reserved.
*/

namespace Poodle\SQL\Interfaces;

interface ResultIterator extends Result, \Iterator
{
	# Iterator: rewind(), valid(), current(), key(), next()
}
