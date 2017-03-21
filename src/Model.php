<?php
namespace SlaxWeb\Cache\Database;

use SlaxWeb\Cache\Manager as CacheManager;
use SlaxWeb\Cache\Exception\CacheException;
use SlaxWeb\Database\Interfaces\Result as ResultInterface;

/**
 * Cache Database Model Extension
 *
 * The Model Extension class overrides the required methods to store the obtained
 * data into cache, and return it before the actuall call is made, and adds other
 * required methods to manipulate the cache.
 *
 * @package   SlaxWeb\Cache
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.1
 */
abstract class Model extends \SlaxWeb\Database\BaseModel
{
    /**
     * Cache Manager object
     *
     * @var \SlaxWeb\CacheManager
     */
    protected $cache = null;

    /**
     * Cache name
     *
     * @var string
     */
    protected $cachename = "";

    /**
     * Skip cache
     *
     * @var bool
     */
    protected $skipCache = true;

    /**
     * Set Cache
     *
     * Sets the Cache Manager to the model that is used later to obtain and store
     * data from and to cache.
     *
     * @param \SlaxWeb\Cache\Manager $cache Cache Manager object
     * @return self
     */
    public function setCache(CacheManager $cache): Model
    {
        $this->cache = $cache;
        $this->skipCache = false;
        return $this;
    }

    /**
     * Skip cache
     *
     * The next call to retrieve data will ignore cache, nor will it store the retrieved
     * data to cache.
     *
     * @return self
     */
    public function skipCache(): Model
    {
        $this->skipCache = true;
        return $this;
    }

    /**
     * Set Cache Name
     *
     * Sets the cache name that will be included to the default cache name when
     * writting to cache. If the cache name is set, the 'update' method will automatically
     * remove this models cache for that name.
     *
     * @param string $name Cache name
     * @return self
     */
    public function setCacheName(string $name): Model
    {
        $this->cacheName = $name;
        return $this;
    }

    /**
     * @inheritDoc
     *
     * Checks if the cache contains a record for the desired query, and returns
     * the cached result object.
     */
    public function select(array $columns): ResultInterface
    {
        $name = "database_{$this->table}{$this->cacheName}_"
            . sha1(
                $this->qBuilder->getPredicates()->convert()
                . implode("", $columns)
            );
        $this->cacheName = "";

        if ($this->skipCache === false) {
            try {
                $this->result = $this->cache->read($name);
                $this->skipCache = false;
                return $this->result;
            } catch (CacheException $e) {
                $this->logger->info(
                    "Error trying to obtain data from cache for query. Proceeding "
                    . "with normal execution of query."
                );
            }
        }

        parent::select($columns);
        if ($this->skipCache === false) {
            $this->cache->write($name, $this->result);
            $this->skipCache = false;
        }

        return $this->result;
    }

    /**
     * @inheritDoc
     *
     * If the 'cacheName' property is set, then the cached data that contanis that
     * name will be removed.
     */
    public function update(array $columns): bool
    {
        if ($this->cacheName !== "") {
            $this->cache->remove("database_{$this->table}{$this->cacheName}", true);
            $this->cacheName= "";
        }

        return parent::update($columns);
    }
}
