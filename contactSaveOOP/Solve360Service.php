<?php
/**
 * Service gateway class used to access Solve360 via external API
 *
 * LICENSE
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @copyright  Copyright (c) Norada Corporation (http://www.norada.com)
 * @version    0.8
 */
class Solve360Service
{
    /**
     * @var string
     */
    const ITEM_CONTACTS            = 'contacts';
    /**
     * @var string
     */
    const ITEM_COMPANIES           = 'companies';
    /**
     * @var string
     */
    const ITEM_PROJECTBLOGS        = 'projectblogs';

    /**
     * @var string
     */
    const ACTIVITY_CALL            = 'call';
    /**
     * @var string
     */
    const ACTIVITY_COMMENT         = 'comment';
    /**
     * @var string
     */
    const ACTIVITY_FILE            = 'file';
    /**
     * @var string
     */
    const ACTIVITY_GOOGLE_DOCS     = 'googledoc';
    /**
     * @var string
     */
    const ACTIVITY_LINKED_EMAIL    = 'linkedemails';
    /**
     * @var string
     */
    const ACTIVITY_NOTE            = 'note';
    /**
     * @var string
     */
    const ACTIVITY_PHOTO           = 'photo';
    /**
     * @var string
     */
    const ACTIVITY_PHOTO_LIST      = 'photolist';
    /**
     * @var string
     */
    const ACTIVITY_TASK            = 'task';
    /**
     * @var string
     */
    const ACTIVITY_TASK_LIST       = 'tasklist';
    /**
     * @var string
     */
    const ACTIVITY_WEBSITE         = 'website';
    /**
     * @var string
     */
    const ACTIVITY_EVENT           = 'event';
    /**
     * @var string
     */
    const ACTIVITY_EVENTEX         = 'eventex';
    /**
     * @var string
     */
    const ACTIVITY_RECURRING_EVENT = 'recurringevent';
    /**
     * @var string
     */
    const ACTIVITY_SEPARATOR       = 'separator';
    /**
     * @var string
     */
    const ACTIVITY_OPPORTUNITY     = 'opportunity';
    /**
     * @var string
     */
    const ACTIVITY_TIMERECORD      = 'timerecord';
    /**
     * @var string
     */
    const ACTIVITY_TEMPLATE        = 'template';

    /**
     * @var string
     */
    const RESTVERB_POST            = 'POST';
    /**
     * @var string
     */
    const RESTVERB_PUT             = 'PUT';
    /**
     * @var string
     */
    const RESTVERB_DELETE          = 'DELETE';
    /**
     * @var string
     */
    const RESTVERB_GET             = 'GET';


    /**
     * Base64 encoded string that is used for the authentication
     *
     * @var string
     */
    protected $_credentials = '';

    /**
     * Host to connect to
     *
     * @var string
     */
    protected $_host = 'secure.solve360.com';

    /**
     * Should the connection be secure (http over ssl)
     *
     * @var string
     */
    protected $_connectionSecure = true;

    /**
     * Connection timeout, in seconds
     *
     * @var int
     */
    protected $_timeout = 30;

    /**
     * Whether requests should be performed in batch mode
     *
     * @var bool
     */
    protected $_batchMode = false;

    /**
     * Collection of requests for a batch call
     *
     * @var array
     */
    protected $_batchRequests = array();

    /**
     * Constructor
     *
     * @param string $userEmail user email that is used for login to Solve360
     * @param string $userApiToken api token, may be seen in Workspace -> My Account -> API Token
     * @param string $host host to connect to (if different than secure.solve360.com)
     * @param bool $connectionSecure is the connection secure (true by default)
     * @return Solve360Service
     */
    public function __construct($userEmail, $userApiToken, $host = null, $connectionSecure = true)
    {
        $this->setCredentials($userEmail, $userApiToken);
        if ($host !== null) {
            $this->setHost($host);
        }
        $this->setConnectionSecure($connectionSecure);
    }

    /**
     * Sets the credentials authentication string
     *
     * @param $userEmail
     * @param $userApiToken
     * @return boolean
     */
    public function setCredentials($userEmail, $userApiToken)
    {
        $this->_credentials = base64_encode($userEmail . ':' . $userApiToken);

        return true;
    }

    /**
     * Gets the credential base64 encoded string
     *
     * @return string
     */
    public function getCredentials()
    {
        return $this->_credentials;
    }

    /**
     * Sets if the connection is secure or not
     *
     * @param $connectionSecure boolean
     * @return boolean
     */
    public function setConnectionSecure($connectionSecure = true)
    {
        $this->_connectionSecure = $connectionSecure ? true : false;

        return true;
    }

    /**
     * Sets the host
     *
     * @param $host
     * @return boolean
     */
    public function setHost($host)
    {
        $this->_host = $host;

        return true;
    }

    /**
     * Returns the host
     *
     * @return string
     */
    public function getHost()
    {
        return $this->_host;
    }

    /**
     * Return wether the connection is secure or it is not
     *
     * @return boolean
     */
    public function isConnectionSecure()
    {
        return $this->_connectionSecure ? true : false;
    }

    /**
     * Returns the timeout
     *
     * @return int
     */
    public function getTimeout()
    {
        return $this->_timeout;
    }

    /**
     * Sets the timeout
     *
     * @param $timeout
     * @return boolean
     */
    public function setTimeout($timeout)
    {
        $this->_timeout = $timeout;

        return true;
    }

    /**
     * Opens connection to Solve360 server and returns the handler
     *
     * @return resource
     * @throws Exception
     */
    protected function _connect()
    {
        if ($this->isConnectionSecure()) {
            $host = "ssl://" . $this->getHost();
            $port = 443;
        } else {
            $host = "tcp://" . $this->getHost();
            $port = 80;
        }

        $connection = stream_socket_client(
            $host . ':' . $port,
            $errno,
            $errstr,
            (int) $this->getTimeout(),
            STREAM_CLIENT_CONNECT
        );

        if (!$connection) {
            throw new Exception('Can\'t connect to the host: ' . $this->getHost());
        }

        // Set the timeout
        stream_set_timeout($connection, $this->getTimeout());

        return $connection;
    }

    /**
     * Convert data to the xml format. Called recursively
     *
     * @param array $data
     * @param SimpleXmlElement $xml
     * @param string $parent
     * @return string XML
     */
    protected function _prepareData(array $data, $xml = null, $parent = null)
    {
        if ($xml === null) {
            $xml = simplexml_load_string('<?xml version="1.0" encoding="utf-8"?><request/>');
        }
        foreach($data as $key => $value) {
            if (is_array($value)) {
                $arrayKeys = array_keys($value);
                if (is_numeric(array_pop($arrayKeys))) {
                    $this->_prepareData($value, $xml, $key);
                } else {
                    $node = $xml->addChild($key);
                    $this->_prepareData($value, $node);
                }
            } else {
                // Escape values with htmlspecialchars
                $xml->addChild(($parent === null ? $key : $parent), htmlspecialchars($value));
            }
        }

        return $xml->asXML();
    }

    /**
     * Enables batch mode
     * When in batch mode, all requests are postponed and only issued when you call
     * ->requestBatch()
     */
    public function enableBatchMode()
    {
        $this->_batchMode = true;
    }

    /**
     * Disables batch mode
     * All previously stored requests are preserved
     */
    public function disableBatchMode()
    {
        $this->_batchMode = false;
    }

    /**
     * Issues previously recorded requests
     * Returns responses as an array, accordingly.
     *
     * @return array
     */
    public function requestBatch()
    {
        $boundary = '====' . str_replace('"', '', mt_rand()) . '===';
        $data = '';
        while ($request = array_shift($this->_batchRequests)) {
            $data .= '--' . $boundary . "\r\n"
                . "Content-Type: application/http\r\n"
                . "Content-Transfer-Encoding: binary\r\n"
                . "Content-ID: \r\n\r\n"
                . $request . "\r\n";
        }
        $data .= '--' . $boundary . '--';
        $batchRequest = "POST /batch HTTP/1.1\r\n"
            . "Host: " . $this->getHost() . "\r\n"
            . "Authorization: Basic " . $this->getCredentials() . "\r\n"
            . "Content-Type: multipart/mixed; boundary=$boundary\r\n"
            . "Content-Length: " . strlen($data) . "\r\n"
            . "Connection: close\r\n\r\n"
            . $data . "\r\n";

        $batchResponse = $this->_request($batchRequest);
        return $this->_parseBatchResponse($batchResponse);
    }

    protected function _parseBatchResponse($batchResponse)
    {
        $responses = array();
        // figuring out boundary value
        $boundaryStart = strpos($batchResponse, 'boundary=') + 9;
        $boundaryEnd = strpos($batchResponse, "\n", $boundaryStart);
        $boundary = trim(substr($batchResponse, $boundaryStart, $boundaryEnd - $boundaryStart));

        $rawResponses = explode("--$boundary", $batchResponse);
        array_shift($rawResponses);
        array_pop($rawResponses);
        foreach ($rawResponses as $response) {
            $responses[] = $this->_responseToXml($response);
        }

        return $responses;
    }

    /**
     * Request to the Solve360 API server
     * When in batch mode request is scheduled and a key (integer) for the response is returned instead
     *
     * @param string $uri URI of the resource
     * @param string $restVerb REST verb to use (POST, GET, PUT, DELETE)
     * @param array $data Data that will be converted to xml format and send to the server
     * @return SimpleXMLElement|integer
     * @throws Exception
     */
    public function request($uri, $restVerb, $data = array())
    {
        // convert data to xml format
        $data = $data ? $this->_prepareData($data) : '';

        // Prepare request body
        $request = "$restVerb $uri HTTP/1.1\r\n"
            . "Host: " . $this->getHost() . "\r\n"
            // Authorization header
            . "Authorization: Basic " . $this->getCredentials() . "\r\n"
            // We inform the server that we're sending data in xml format
            . "Content-Type: application/xml\r\n"
            // We inform the server that we are waiting for xml in response
            . "Accept: application/xml\r\n"
            . "Content-Length: " . strlen($data) . "\r\n"
            . "Connection: close\r\n\r\n"
            . $data;

        if ($this->_batchMode) {
            // when in batch mode we schedule the requests and return
            // a key (integer) by which the appropriate response may be retrieved later
            $this->_batchRequests[] = $request;
            return key($this->_batchRequests);
        } else {
            // otherwise, we perform request immediately, parse and return it as xml
            return $this->_responseToXml($this->_request($request));
        }
    }

    /**
     * Performs HTTP request
     *
     * @param $request
     * @return string
     * @throws Exception if connection times out
     */
    protected function _request($request)
    {
        $connection = $this->_connect();
        fwrite($connection, $request);
        $response = '';
        do {
            $buff = @fread($connection, 8192);
            if ($buff === false || strlen($buff) === 0) {
                $info = stream_get_meta_data($connection);
                if ($info['timed_out']) {
                    throw new Exception('Connection timed out');
                }
                break;
            } else {
                $response .= $buff;
            }

        } while (feof($connection) === false);
        return $response;
    }

    /**
     * Converts response body to a SimpleXmlElement
     *
     * @param $response
     * @return SimpleXMLElement
     * @throws Exception
     */
    protected function _responseToXml($response)
    {
        // assuming there are headers and meta included:
        $response = substr($response, (strpos($response, "\r\n\r\n") + 4));
        $body = substr($response, (strpos($response, "\r\n\r\n") + 4));
        // Get xml from response
        $matches = array();
        preg_match("/(<.*>)/s", $body, $matches);
        if (!isset($matches[1])) {
            throw new Exception('Can\'t get xml from the response');
        }
        $xml = $matches[1];
        if ($xml) {
            // Convert it to the SimpleXmlElement
            $xml = simplexml_load_string($xml);
        } else {
            // Something went wrong and we haven't got xml in the response
            throw new Exception('System error while working with Solve360 service');
        }

        return $xml;
    }

    /**
     * Creates the item of the specified type
     *
     * @param $itemsType
     * @param $data
     * @return SimpleXMLElement
     */
    public function addItem($itemsType, $data)
    {
        $uri = '/' . $itemsType . '/';

        return $this->request($uri, self::RESTVERB_POST, $data);
    }

    /**
     * Creates contact
     *
     * @param $data
     * @return SimpleXMLElement
     */
    public function addContact($data)
    {
        return $this->addItem(self::ITEM_CONTACTS, $data);
    }

    /**
     * Creates company
     *
     * @param $data
     * @return SimpleXMLElement
     */
    public function addCompany($data)
    {
        return $this->addItem(self::ITEM_COMPANIES, $data);
    }

    /**
     * Creates projectblog
     *
     * @param $data
     * @return SimpleXMLElement
     */
    public function addProjectblog($data)
    {
        return $this->addItem(self::ITEM_PROJECTBLOGS, $data);
    }

    /**
     * Deletes item of specified type
     *
     * @param $itemsType
     * @param $id
     * @return SimpleXMLElement
     */
    public function deleteItem($itemsType, $id)
    {
        $uri = '/' . $itemsType . '/' . (string) $id;

        return $this->request($uri, self::RESTVERB_DELETE);
    }

    /**
     * Deletes contact
     *
     * @param $id
     * @return SimpleXMLElement
     */
    public function deleteContact($id)
    {
        return $this->deleteItem(self::ITEM_CONTACTS, $id);
    }

    /**
     * Deletes company
     *
     * @param $id
     * @return SimpleXMLElement
     */
    public function deleteCompany($id)
    {
        return $this->deleteItem(self::ITEM_COMPANIES, $id);
    }

    /**
     * Deletes projectblog
     *
     * @param $id
     * @return SimpleXMLElement
     */
    public function deleteProjectblog($id)
    {
        return $this->deleteItem(self::ITEM_PROJECTBLOGS, $id);
    }

    /**
     * Returns item of specified type
     *
     * @param $itemsType
     * @param $id
     * @return SimpleXMLElement
     */
    public function getItem($itemsType, $id)
    {
        $uri = '/' . $itemsType . '/' . (string) $id;

        return $this->request($uri, self::RESTVERB_GET);
    }

    /**
     * Returns contact item
     *
     * @param $id
     * @return SimpleXMLElement
     */
    public function getContact($id)
    {
        return $this->getItem(self::ITEM_CONTACTS, $id);
    }

    /**
     * Returns company item
     *
     * @param $id
     * @return SimpleXMLElement
     */
    public function getCompany($id)
    {
        return $this->getItem(self::ITEM_COMPANIES, $id);
    }

    /**
     * Returns project blog item
     *
     * @param $id
     * @return SimpleXMLElement
     */
    public function getProjectblog($id)
    {
        return $this->getItem(self::ITEM_PROJECTBLOGS, $id);
    }

    /**
     * Changes item of specified type
     *
     * @param $itemsType
     * @param $id
     * @param $data
     * @return SimpleXMLElement
     */
    public function editItem($itemsType, $id, $data)
    {
        $uri = '/' . $itemsType . '/' . $id;

        return $this->request($uri, self::RESTVERB_PUT, $data);
    }

    /**
     * Search items for specified options
     *
     * @param $itemsType
     * @param $searchOptions
     * @return SimpleXMLElement
     */
    public function searchItems($itemsType, $searchOptions = array())
    {
        $uri = '/' . $itemsType . '/';

        return $this->request($uri, self::RESTVERB_GET, $searchOptions);
    }

    /**
     * Search contacts for specified options
     *
     * @param $searchOptions
     * @return SimpleXMLElement
     */
    public function searchContacts($searchOptions)
    {
        return $this->searchItems(self::ITEM_CONTACTS, $searchOptions);
    }

    /**
     * Search companies for specified options
     *
     * @param $searchOptions
     * @return SimpleXMLElement
     */
    public function searchCompanies($searchOptions)
    {
        return $this->searchItems(self::ITEM_COMPANIES, $searchOptions);
    }

    /**
     * Search projectblogs for specified options
     *
     * @param $searchOptions
     * @return SimpleXMLElement
     */
    public function searchProjectblogs($searchOptions)
    {
        return $this->searchItems(self::ITEM_PROJECTBLOGS, $searchOptions);
    }

    /**
     * Returns the number of items of specified types in the system
     *
     * @param $itemsType
     * @return int
     * @throws Exception when called in batch mode
     */
    public function getItemsCount($itemsType)
    {
        if ($this->_batchMode) {
            throw new Exception('getItemsCount is not available in batch mode');
        }
        $uri = '/' . $itemsType . '/';
        // Set minimal limit - we just need to know how many items we have
        $searchOptions = array('limit' => 1);
        // Get the response
        $response = $this->request($uri, self::RESTVERB_GET, $searchOptions);

        // Return the number of the items
        return (int) $response->count;
    }

    /**
     * Returns the number of contacts in the system
     *
     * @return int
     */
    public function getContactsCount()
    {
        return $this->getItemsCount(self::ITEM_CONTACTS);
    }

    /**
     * Returns the number of companies in the system
     *
     * @return int
     */
    public function getCompaniesCount()
    {
        return $this->getItemsCount(self::ITEM_COMPANIES);
    }

    /**
     * Returns the number of projectblogs in the system
     *
     * @return int
     */
    public function getProjectblogsCount()
    {
        return $this->getItemsCount(self::ITEM_PROJECTBLOGS);
    }

    /**
     * Returns all items of specified type if limit is not set
     *
     * @param $itemsType
     * @param $limit
     * @param $start
     * @return SimpleXMLElement
     * @throws Exception when called in batched mode
     */
    public function getAllItems($itemsType, $limit = null, $start = 1)
    {
        if ($this->_batchMode) {
            throw new Exception('getAllItems is not available in batch mode');
        }
        $uri = '/' . $itemsType . '/';

        if ($limit === null) { // if the limit is not set, we need all items of the specified type
            $limit = $this->getItemsCount($itemsType);
        }

        $searchOptions = array(
            'limit' => $limit,
            'start' => $start
        );

        return $this->request($uri, self::RESTVERB_GET, $searchOptions);
    }

    /**
     * Returns all contacts if limit is not set
     *
     * @param $limit
     * @param $start
     * @return SimpleXMLElement
     */
    public function getAllContacts($limit = null, $start = 1)
    {
        return $this->getAllItems(self::ITEM_CONTACTS, $limit, $start);
    }

    /**
     * Returns all companies if limit is not set
     *
     * @param $limit
     * @param $start
     * @return SimpleXMLElement
     */
    public function getAllCompanies($limit = null, $start = 1)
    {
        return $this->getAllItems(self::ITEM_COMPANIES, $limit, $start);
    }

    /**
     * Returns all projectblogs if limit is not set
     *
     * @param $limit
     * @param $start
     * @return SimpleXMLElement
     */
    public function getAllProjectblogs($limit = null, $start = 1)
    {
        return $this->getAllItems(self::ITEM_PROJECTBLOGS, $limit, $start);
    }

    /**
     * Changes contact
     *
     * @param $id
     * @param $data
     * @return SimpleXMLElement
     */
    public function editContact($id, $data)
    {
        return $this->editItem(self::ITEM_CONTACTS, $id, $data);
    }

    /**
     * Changes company
     *
     * @param $id
     * @param $data
     * @return SimpleXMLElement
     */
    public function editCompany($id, $data)
    {
        return $this->editItem(self::ITEM_COMPANIES, $id, $data);
    }

    /**
     * Changes projectblog
     *
     * @param $id
     * @param $data
     * @return SimpleXMLElement
     */
    public function editProjectblog($id, $data)
    {
        return $this->editItem(self::ITEM_PROJECTBLOGS, $id, $data);
    }

    /**
     * Creates activity
     *
     * @param $parentId
     * @param $activityType
     * @param $data
     * @param $itemsType
     * @param $positionBefore
     * @param $positionAfter
     * @return SimpleXMLElement
     * @throws Exception if activity type is not specified
     */
    public function addActivity($parentId, $activityType, $data, $itemsType = self::ITEM_CONTACTS, $positionBefore = null, $positionAfter = null)
    {
        if (!$activityType) {
            throw new Exception ('Activity type needs to be set');
        }
        $uri = '/' . $itemsType . '/' . $activityType . '/';
        $data = array(
            'parent' => $parentId,
            'data'   => $data
        );
        if ($positionBefore !== null) {
            $data['before'] = $positionBefore;
        }
        if ($positionAfter !== null) {
            $data['after'] = $positionAfter;
        }

        return $this->request($uri, self::RESTVERB_POST, $data);
    }

    /**
     * Deletes activity
     *
     * @param $activityId
     * @param $activityType
     * @param $itemsType
     * @return SimpleXMLElement
     */
    public function deleteActivity($activityId, $activityType, $itemsType = self::ITEM_CONTACTS)
    {
        $uri = '/' . $itemsType . '/' . $activityType . '/' . $activityId;

        return $this->request($uri, self::RESTVERB_DELETE);
    }

    /**
     * Changes activity
     *
     * @param $activityId
     * @param $activityType
     * @param $data
     * @param $itemsType
     * @param $positionBefore
     * @param $positionAfter
     * @param $newParent
     * @return SimpleXMLElement
     */
    public function editActivity($activityId, $activityType, $data, $itemsType = self::ITEM_CONTACTS, $positionBefore = null, $positionAfter = null, $newParent = null)
    {
        $uri = '/' . $itemsType . '/' . $activityType . '/' . $activityId;
        if (empty($data)) {
            $data = array();
        } else {
            $data = array ('data' => $data);
        }
        if ($positionBefore !== null) {
            $data['before'] = $positionBefore;
        }
        if ($positionAfter !== null) {
            $data['after'] = $positionAfter;
        }
        if ($newParent !== null) {
            $data['parent'] = $newParent;
        }

        return $this->request($uri, self::RESTVERB_PUT, $data);
    }

    /**
     * Returns all categories for items of specifed type
     *
     * @param $itemsType
     * @return SimpleXMLElement
     */
    public function getCategories($itemsType)
    {
        $uri = '/' . $itemsType . '/categories/';

        return $this->request($uri, self::RESTVERB_GET);
    }

    /**
     * Returns all contacts categories
     *
     * @return SimpleXMLElement
     */
    public function getContactCategories()
    {
        return $this->getCategories(self::ITEM_CONTACTS);
    }

    /**
     * Returns all companies categories
     *
     * @return SimpleXMLElement
     */
    public function getCompanyCategories()
    {
        return $this->getCategories(self::ITEM_COMPANIES);
    }

    /**
     * Returns all projectblogs categories
     *
     * @return SimpleXMLElement
     */
    public function getProjectblogCategories()
    {
        return $this->getCategories(self::ITEM_PROJECTBLOGS);
    }

    /**
     * Categorize item
     *
     * @param $itemId
     * @param $categoryId
     * @param $itemsType
     * @return SimpleXMLElement
     */
    public function categorizeItem($itemId, $categoryId, $itemsType = self::ITEM_CONTACTS)
    {
        $uri = '/' . $itemsType . '/' . $itemId;
        $data = array(
            'categories' => array (
                'add' => array (
                    'category' => $categoryId
                )
            )
        );

        return $this->request($uri, self::RESTVERB_PUT, $data);
    }

    /**
     * Categorize contact
     *
     * @param $contactId
     * @param $categoryId
     * @return SimpleXMLElement
     */
    public function categorizeContact($contactId, $categoryId)
    {
        return $this->categorizeItem($contactId, $categoryId, self::ITEM_CONTACTS);
    }

    /**
     * Categorize company
     *
     * @param $companyId
     * @param $categoryId
     * @return SimpleXMLElement
     */
    public function categorizeCompany($companyId, $categoryId)
    {
        return $this->categorizeItem($companyId, $categoryId, self::ITEM_COMPANIES);
    }

    /**
     * Categorize projectblog
     *
     * @param $projectblogId
     * @param $categoryId
     * @return SimpleXMLElement
     */
    public function categorizeProjectblog($projectblogId, $categoryId)
    {
        return $this->categorizeItem($projectblogId, $categoryId, self::ITEM_PROJECTBLOGS);
    }

    /**
     * Uncategorize item
     *
     * @param $itemId
     * @param $categoryId
     * @param $itemsType
     * @return SimpleXMLElement
     */
    public function uncategorizeItem($itemId, $categoryId, $itemsType = self::ITEM_CONTACTS)
    {
        $uri = '/' . $itemsType . '/' . $itemId;
        $data = array(
            'categories' => array (
                'remove' => array (
                    'category' => $categoryId
                )
            )
        );

        return $this->request($uri, self::RESTVERB_PUT, $data);
    }

    /**
     * Uncategorize contact
     *
     * @param $contactId
     * @param $categoryId
     * @return SimpleXMLElement
     */
    public function uncategorizeContact($contactId, $categoryId)
    {
        return $this->uncategorizeItem($contactId, $categoryId, self::ITEM_CONTACTS);
    }

    /**
     * Uncategorize company
     *
     * @param $companyId
     * @param $categoryId
     * @return SimpleXMLElement
     */
    public function uncategorizeCompany($companyId, $categoryId)
    {
        return $this->uncategorizeItem($companyId, $categoryId, self::ITEM_COMPANIES);
    }

    /**
     * Uncategorize projectblog
     *
     * @param $projectblogId
     * @param $categoryId
     * @return SimpleXMLElement
     */
    public function uncategorizeProjectblog($projectblogId, $categoryId)
    {
        return $this->uncategorizeItem($projectblogId, $categoryId, self::ITEM_PROJECTBLOGS);
    }

    /**
     * Create category
     *
     * @param $categoryLabel
     * @param $itemsType
     * @return SimpleXMLElement
     */
    public function createCategory($categoryLabel, $itemsType)
    {
        $uri = '/' . $itemsType . '/categories/';
        $data = array('name' => $categoryLabel);

        return $this->request($uri, self::RESTVERB_POST, $data);
    }

    /**
     * Create contact category
     *
     * @param $categoryLabel
     * @return SimpleXMLElement
     */
    public function createContactCategory($categoryLabel)
    {
        return $this->createCategory($categoryLabel, self::ITEM_CONTACTS);
    }

    /**
     * Create company category
     *
     * @param $categoryLabel
     * @return SimpleXMLElement
     */
    public function createCompanyCategory($categoryLabel)
    {
        return $this->createCategory($categoryLabel, self::ITEM_COMPANIES);
    }

    /**
     * Create projectblog category
     *
     * @param $categoryLabel
     * @return SimpleXMLElement
     */
    public function createProjectblogCategory($categoryLabel)
    {
        return $this->createCategory($categoryLabel, self::ITEM_PROJECTBLOGS);
    }

    /**
     * Add item relation
     *
     * @param $itemId
     * @param $relatedtoId
     * @param $itemsType
     * @return SimpleXMLElement
     */
    public function addItemRelation($itemId, $relatedtoId, $itemsType = self::ITEM_CONTACTS)
    {
        $uri = '/' . $itemsType . '/' . $itemId;
        $data = array(
            'relateditems' => array (
                'add' => array (
                    'relatedto' => array('id' => $relatedtoId)
                )
            )
        );

        return $this->request($uri, self::RESTVERB_PUT, $data);
    }

    /**
     * Add contact relation
     *
     * @param $contactId
     * @param $relatedtoId
     * @return SimpleXMLElement
     */
    public function addContactRelation($contactId, $relatedtoId)
    {
        return $this->addItemRelation($contactId, $relatedtoId, self::ITEM_CONTACTS);
    }

    /**
     * Add company relation
     *
     * @param $companyId
     * @param $relatedtoId
     * @return SimpleXMLElement
     */
    public function addCompanyRelation($companyId, $relatedtoId)
    {
        return $this->addItemRelation($companyId, $relatedtoId, self::ITEM_COMPANIES);
    }

    /**
     * Add project blog relation
     *
     * @param $projectblogId
     * @param $relatedtoId
     * @return SimpleXMLElement
     */
    public function addProjectblogRelation($projectblogId, $relatedtoId)
    {
        return $this->addItemRelation($projectblogId, $relatedtoId, self::ITEM_PROJECTBLOGS);
    }

    /**
     * Remove item relation
     *
     * @param $itemId
     * @param $relatedtoId
     * @param $itemsType
     * @return SimpleXMLElement
     */
    public function removeItemRelation($itemId, $relatedtoId, $itemsType = self::ITEM_CONTACTS)
    {
        $uri = '/' . $itemsType . '/' . $itemId;
        $data = array(
            'relateditems' => array (
                'remove' => array (
                    'relatedto' => array('id' => $relatedtoId)
                )
            )
        );

        return $this->request($uri, self::RESTVERB_PUT, $data);
    }

    /**
     * Remove contact relation
     *
     * @param $contactId
     * @param $relatedtoId
     * @return SimpleXMLElement
     */
    public function removeContactRelation($contactId, $relatedtoId)
    {
        return $this->removeItemRelation($contactId, $relatedtoId, self::ITEM_CONTACTS);
    }

    /**
     * Remove company relation
     *
     * @param $companyId
     * @param $relatedtoId
     * @return SimpleXMLElement
     */
    public function removeCompanyRelation($companyId, $relatedtoId)
    {
        return $this->removeItemRelation($companyId, $relatedtoId, self::ITEM_COMPANIES);
    }

    /**
     * Remove project blog relation
     *
     * @param $projectblogId
     * @param $relatedtoId
     * @return SimpleXMLElement
     */
    public function removeProjectblogRelation($projectblogId, $relatedtoId)
    {
        return $this->removeItemRelation($projectblogId, $relatedtoId, self::ITEM_PROJECTBLOGS);
    }
}