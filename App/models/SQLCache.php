<?php
class Models_SQLCache
{

	private static $instance;
	private $cache = array();
	private $useCache = true;

	private function __construct()
	{
	}

	public static function getInstance()
	{
		if (!isset(self::$instance)) 
		{
			$c = __CLASS__;
			self::$instance = new $c;
		}
		return self::$instance;
	}

	public function getAllCache()
	{
		return $this->cache;
	}

	public function getCache($cKey)
	{
		if ($cKey != '') 
		{
			if ($this->cacheDataAvailable($cKey)) 
			{
				return unserialize($this->cache[$cKey]);
			}
		}
	}

	public function emptyCache()
	{
		unset($this->cache);
		$this->cache = array();
	}

	public function ignoreCache()
	{
		$this->useCache = false;
	}

	public function setCache($cKey, $object)
	{
		$this->cache[$cKey] = serialize($object);
	}

	private function cacheDataAvailable($cKey)
	{
		if (array_key_exists($cKey, $this->cache)) 
		{
			return true;
		}
		else 
		{
			return false;
		}
	}


	public function generateCacheKey($sql, $bind = array())
	{
		$bindStr = '';
		if(!empty($bind))
		{
			foreach($bind as $key=>$val)
			{
				$bindStr .= $key ."=". $val;
			}
		}
		
		return md5($sql . $bindStr);
	}


	public function getData($sql, $bind, $cached=true)
	{
		global $db;

		$cKey = $this->generateCacheKey($sql, $bind);
		if (($this->useCache) && ($cached && $this->cacheDataAvailable($cKey))) 
		{
			$object = unserialize($this->cache[$cKey]);
		}
		else 
		{
			$object = $db->select($sql, $bind);
			$this->setCache($cKey, $object);
		}

		$this->useCache = true;
		return $object;
	}
	
	public function getOne($sql, $bind, $cached=true)
	{
		global $db;
				
		$cKey = $this->generateCacheKey($sql, $bind, 0);
		if (($this->useCache) && ($cached && $this->cacheDataAvailable($cKey))) 
		{
			$object = unserialize($this->cache[$cKey]);
		}
		else 
		{
			//$object = $db->fetchOne($sql, $bind);

			$object = $db->selectOne($sql, $bind);
			$this->setCache($cKey, $object);
		}
		
		$this->useCache = true;
		
		return $object;
	}
}
?>