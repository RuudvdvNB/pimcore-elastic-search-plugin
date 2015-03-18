<?php

/**
 *
 * @author      Michal Maszkiewicz
 * @package     
 */

namespace ElasticSearch\Repository;

use Document_Page;
use Elasticsearch\Client;
use Elasticsearch\Endpoints\Delete as DeleteEndpoint;
use ElasticSearch\Filter\FilterInterface;
use ElasticSearch\Processor\Page\PageProcessor;
use InvalidArgumentException;
use NF\HtmlToText;



class PageRepository
{
    /**
     * 
     * @var string
     */
    protected $index;

    /**
     * 
     * @var string
     */
    protected $type;
    
    /**
     * 
     * @var Client
     */
    protected $client;
    
    /**
     *
     * @var DeleteEndpoint
     */
    protected $deleteEndpoint;
    
    /**
     * 
     * @var HtmlToText
     */
    protected $htmlToTextFilter;
    
    /**
     *
     * @var PageProcessor 
     */
    protected $processor;
    
    /**
     *
     * @var FilterInterface
     */
    protected $inputFilter;
    
    

    /**
     * @param $configuration
     * @param Client $client
     * @param $htmlToTextFilter
     * @param PageProcessor $processor
     * @param FilterInterface $inputFilter
     */
    public function __construct(
        array $configuration,
        Client $client,
        HtmlToText $htmlToTextFilter,
        PageProcessor $processor,
        FilterInterface $inputFilter
    ) {
        if (! isset($configuration['index'])) {
            throw new InvalidArgumentException('Missing configuration setting: index');
        }

        if (! isset($configuration['type'])) {
            throw new InvalidArgumentException('Missing configuration setting: type');
        }

        $this->index = (string) $configuration['index'];
        $this->type = (string) $configuration['type'];
        $this->client = $client;
        $this->htmlToTextFilter = $htmlToTextFilter;
        $this->processor = $processor;
        $this->inputFilter = $inputFilter;
    }

    /**
     * @param Document_Page $document
     * @return array
     */
    public function delete(Document_Page $document)
    {
        $params = array(
            'id' => $document->getId(),
            'index' => $this->index,
            'type' => $this->type
        );

        if (! $this->exists($document)) {

            return false;

        }

        return $this->client->delete($params);
    }
    
    /**
     * Clears all entries from this index
     * 
     * @return array
     */
    public function clear()
    {
        $this->client->indices()->deleteMapping([
            'index' => $this->index,
            'type' => $this->type
        ]);
    }

    /**
     * @param Document_Page $document
     */
    public function save(Document_Page $document)
    {
        $params = $this->pageToArray($document);

        if ($this->exists($document)) {
            $this->client->update($params);
        } else {
            $this->client->create($params);
        }
    }

    /**
     * @param Document_Page $document
     * @return array
     */
    public function exists(Document_Page $document)
    {
        $params = array(
            'id' => $document->getId(),
            'index' => $this->index,
            'type' => $this->type
        );

        return $this->client->exists($params);
    }

    /**
     * Executes an ElasticSearch "bool" query
     * 
     * @param array $mustCriteria
     * @param array $shouldCriteria
     * @param array $mustNotCriteria
     * @return Document_Page[]
     */
    public function findBy(
        array $mustCriteria = [],
        array $shouldCriteria = [],
        array $mustNotCriteria = [],
        $offset = null,
        $limit = null
    ) {
        $body = [
            'query' => [
                'bool' => [
                    'must' => $mustCriteria,
                    'should' => $shouldCriteria,
                    'must_not' => $mustNotCriteria
                ]
            ]
        ];
        
        foreach (['offset', 'limit'] as $constraint) {
            $constraintValue = $$constraint;
            
            if ($constraintValue !== null) {
                $body[$constraint] = $constraintValue;
            }
        }
        
        $result = $this->client->search([
            'index' => $this->index,
            'type' => $this->type,
            'body' => $body
        ]);

        $documents = [];

        if (!isset($result['hits']['hits'])) {
            return [];
        }

        // Fetch list of documents based on results from Elastic Search
        // TODO optimize to use list
        foreach ($result['hits']['hits'] as $page) {
            $id = (int) $page['_id'];
            
            if (($document = Document_Page::getById($id)) instanceof Document_Page) {
                $documents[] = $document;
            }
        }

        return $documents;
    }
    
    /**
     * Finds documents by text
     *
     * @param string $text
     * @param array $filters
     * @param array $terms
     * @return Document_Page[]
     */
    public function query(
        $text,
        array $filters = [],
        $offset = null,
        $limit = null
    ) {
        $mustCriteria = [];
        
        if (!empty($text)) {
            $mustCriteria[]['match']['_all'] = ['query' => (string) $text];
        }
        
        foreach ($filters as $name => $term) {
            $mustCriteria[]['terms'] = [
                $name => [$this->inputFilter->filter($term)],
                'minimum_should_match' => 1
            ];
        }
        
        return $this->findBy($mustCriteria, [], [], $offset, $limit);
    }
    
    /**
     * @param Document_Page $document
     * @return array
     */
    protected function pageToArray(Document_Page $document)
    {
        return [
            'id' => $document->getId(),
            'body' => ['doc' => $this->processor->processPage($document)],
            'index' => $this->index,
            'type' => $this->type,
            'timestamp' => $document->getModificationDate()
        ];
    }
    
}