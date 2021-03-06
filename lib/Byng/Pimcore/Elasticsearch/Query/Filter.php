<?php

/**
 * This file is part of the "byng/pimcore-elasticsearch-plugin" project.
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the LICENSE is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Byng\Pimcore\Elasticsearch\Query;

/**
 * Filter
 *
 * Encapsulates "filter" data.
 *
 * @author Asim Liaquat <asimlqt22@gmail.com>
 */
class Filter implements QueryInterface
{
    /**
     * @var QueryInterface
     */
    private $query;

    /**
     * Filter constructor.
     *
     * @param QueryInterface $query
     */
    public function __construct(QueryInterface $query = null)
    {
        $this->query = $query;
    }

    /**
     * Get bool query
     *
     * @return QueryInterface
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Set bool query
     * 
     * @param QueryInterface $query
     */
    public function setQuery(QueryInterface $query)
    {
        $this->query = $query;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return "filter";
    }
}
