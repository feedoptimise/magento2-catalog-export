<?php
namespace Feedoptimise\CatalogExport\Controller\Source;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

class Index extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /** @var RequestInterface $requestInterface */
    protected $requestInterface;
    /** @var \Feedoptimise\CatalogExport\Helper\Settings $extensionSettings */
    protected $extensionSettings;
    /** @var \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory */
    protected $resultJsonFactory;
    /** @var \Magento\InventoryApi\Api\SourceRepositoryInterface  */
    protected $sourceRepository;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\RequestInterface $requestInterface,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Feedoptimise\CatalogExport\Helper\Settings $extensionSettings,
        \Magento\InventoryApi\Api\SourceRepositoryInterface $sourceRepository
    )
    {
        $this->requestInterface = $requestInterface;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->extensionSettings = $extensionSettings;
        $this->sourceRepository = $sourceRepository;

        return parent::__construct($context);
    }

    public function execute()
    {
        try {

            $request = $this->requestInterface->getParams();
            if(isset($request['debug']) && $request['debug'] == 'true')
            {
                error_reporting(E_ALL);
                ini_set('display_errors', 1);
                register_shutdown_function( "feedoptimise_fatal_handler_source" );
            }
            if(isset($request['phpinfo']) && $request['phpinfo'] == 'true')
            {
                phpinfo();
                die;
            }
            if(isset($request['meminfo']) && $request['meminfo'] == 'true')
            {
                $result = $this->resultJsonFactory->create();
                $meminfo = array();
                if(file_exists("/proc/meminfo"))
                {
                    $data = explode("\n", file_get_contents("/proc/meminfo"));
                    foreach ($data as $line) {
                        list($key, $val) = explode(":", $line);
                        $meminfo[$key] = trim($val);
                    }
                }
                return $result->setData([
                    'error' => false,
                    'code' => 200,
                    'payload' => [
                        'meminfo' => $meminfo,
                    ]
                ]);
            }

            $result = $this->resultJsonFactory->create();
            if(($settingsError = $this->extensionSettings->validateSettings($this->requestInterface->getParams())) !== true)
            {
                return $result->setData($settingsError);
            }
            else
            {
                $source = $this->getSource();
                return $result->setData([
                    'error' => false,
                    'code' => 200,
                    'payload' => [
                        'total' => count($source),
                        'source' => $source
                    ]
                ]);
            }
        }
        catch (\Throwable $e) {
            $result = $this->resultJsonFactory->create();

            $result->setData([
                'error' => true,
                'code' => 500,
                'error_msg' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $result = $this->resultJsonFactory->create();

            $result->setData([
                'error' => true,
                'code' => 500,
                'error_msg' => $e->getMessage()
            ]);
        }
    }

    protected function getSource()
    {
        $results = [];
        try{
            $sourceData = $this->sourceRepository->getList();
            foreach ($sourceData->getItems() as $source){
                $results[] = $source->getData();
            }
        }catch (\Exception $ex){
        }
        return $results;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}

function feedoptimise_fatal_handler_source()
{
    echo json_encode(error_get_last(), JSON_PRETTY_PRINT);
    die;
}