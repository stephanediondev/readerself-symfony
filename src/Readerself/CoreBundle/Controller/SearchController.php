<?php
namespace Readerself\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Readerself\CoreBundle\Controller\AbstractController;

use Readerself\CoreBundle\Manager\ItemManager;
use Readerself\CoreBundle\Manager\CategoryManager;
use Readerself\CoreBundle\Manager\SearchManager;

class SearchController extends AbstractController
{
    protected $itemManager;

    protected $categoryManager;

    protected $actionManager;

    protected $searchManager;

    public function __construct(
        ItemManager $itemManager,
        CategoryManager $categoryManager,
        SearchManager $searchManager
    ) {
        $this->itemManager = $itemManager;
        $this->categoryManager = $categoryManager;
        $this->searchManager = $searchManager;
    }

    /**
     * Search feeds.
     *
     * @ApiDoc(
     *     section="Feed",
     *     headers={
     *         {"name"="X-CONNECTION-TOKEN","required"=true},
     *     },
     *     parameters={
     *         {"name"="q", "dataType"="string", "required"=true, "description"="query"},
     *         {"name"="sortField", "dataType"="string", "required"=false, "format"="""score"", ""title"" or ""date"", default ""score""", "description"=""},
     *         {"name"="sortDirection", "dataType"="string", "required"=false, "format"="""ASC"" or ""DESC"", default ""DESC""", "description"=""},
     *         {"name"="page", "dataType"="integer", "required"=false, "format"="default ""1""", "description"="page number"},
     *         {"name"="perPage", "dataType"="integer", "required"=false, "format"="default ""20""", "description"="items per page"},
     *     },
     * )
     */
    public function feedsAction(Request $request)
    {
        return $this->getResults($request, 'feed');
    }

    /**
     * Search categories.
     *
     * @ApiDoc(
     *     section="Category",
     *     headers={
     *         {"name"="X-CONNECTION-TOKEN","required"=true},
     *     },
     *     parameters={
     *         {"name"="q", "dataType"="string", "required"=true, "description"="query"},
     *         {"name"="sortField", "dataType"="string", "required"=false, "format"="""score"", ""title"" or ""date"", default ""score""", "description"=""},
     *         {"name"="sortDirection", "dataType"="string", "required"=false, "format"="""ASC"" or ""DESC"", default ""DESC""", "description"=""},
     *         {"name"="page", "dataType"="integer", "required"=false, "format"="default ""1""", "description"="page number"},
     *         {"name"="perPage", "dataType"="integer", "required"=false, "format"="default ""20""", "description"="items per page"},
     *     },
     * )
     */
    public function categoriesAction(Request $request)
    {
        return $this->getResults($request, 'category');
    }

    /**
     * Search authors.
     *
     * @ApiDoc(
     *     section="Author",
     *     headers={
     *         {"name"="X-CONNECTION-TOKEN","required"=true},
     *     },
     *     parameters={
     *         {"name"="q", "dataType"="string", "required"=true, "description"="query"},
     *         {"name"="sortField", "dataType"="string", "required"=false, "format"="""score"", ""title"" or ""date"", default ""score""", "description"=""},
     *         {"name"="sortDirection", "dataType"="string", "required"=false, "format"="""ASC"" or ""DESC"", default ""DESC""", "description"=""},
     *         {"name"="page", "dataType"="integer", "required"=false, "format"="default ""1""", "description"="page number"},
     *         {"name"="perPage", "dataType"="integer", "required"=false, "format"="default ""20""", "description"="items per page"},
     *     },
     * )
     */
    public function authorsAction(Request $request)
    {
        return $this->getResults($request, 'author');
    }

    /**
     * Search items.
     *
     * @ApiDoc(
     *     section="Item",
     *     headers={
     *         {"name"="X-CONNECTION-TOKEN","required"=true},
     *     },
     *     parameters={
     *         {"name"="q", "dataType"="string", "required"=true, "description"="query"},
     *         {"name"="sortField", "dataType"="string", "required"=false, "format"="""score"", ""title"" or ""date"", default ""score""", "description"=""},
     *         {"name"="sortDirection", "dataType"="string", "required"=false, "format"="""ASC"" or ""DESC"", default ""DESC""", "description"=""},
     *         {"name"="page", "dataType"="integer", "required"=false, "format"="default ""1""", "description"="page number"},
     *         {"name"="perPage", "dataType"="integer", "required"=false, "format"="default ""20""", "description"="items per page"},
     *     },
     * )
     */
    public function itemsAction(Request $request)
    {
        return $this->getResults($request, 'item');
    }

    private function getResults(Request $request, $type)
    {
        $data = [];
        if(!$memberConnected = $this->validateToken($request)) {
            //return new JsonResponse($data, 403);
        }

        $sortFields = ['date.sort', '_score', 'title.sort'];
        $sortDirection = ['asc', 'desc'];

        if($request->query->get('q')) {
            $page = $request->query->getInt('page', 1);

            if(!array_key_exists($request->query->get('sortField'), $sortFields)) {
                $sortField = '_score';
            } else {
                $sortField = $request->query->get('sortField');
            }
            if(!array_key_exists($request->query->get('sortDirection'), $sortDirection)) {
                $sortDirection = 'desc';
            } else {
                $sortDirection = $request->query->get('sortDirection');
            }

            $size = 20;
            $from = ($size * $page) - 20;
            $path = '/'.$this->searchManager->getIndex().'/_search?size='.intval($size).'&type='.$type.'&from='.intval($from);

            $body = [];
            $body['sort'] = [
                $sortField => [
                    'order' => $sortDirection,
                ],
            ];

            if($type == 'feed') {
                $body['query'] = [
                    'query_string' => [
                        'fields' => ['title', 'description', 'website'],
                        'query' => $request->query->get('q'),
                    ],
                ];
            }

            if($type == 'category') {
                $body['query'] = [
                    'query_string' => [
                        'fields' => ['title'],
                        'query' => $request->query->get('q'),
                    ],
                ];
            }

            if($type == 'author') {
                $body['query'] = [
                    'query_string' => [
                        'fields' => ['title'],
                        'query' => $request->query->get('q'),
                    ],
                ];
            }

            if($type == 'item') {
                $body['query'] = [
                    'query_string' => [
                        'fields' => ['title', 'content', 'feed.title', 'author.title'],
                        'query' => $request->query->get('q'),
                    ],
                ];
            }

            /*if(!$parameters->get('page')->getAttribute('all_languages')) {
                $body['filter'] = array(
                    'term' => array(
                        'feed.language' => 'en',
                    ),
                );
            }*/

            /*if($request->query->get('date_from') && $request->query->get('date_to')) {
                $body['filter'] = array(
                    'range' => array(
                        'date.sort' => array(
                            'gte' => $request->query->get('date_from'),
                            'lte' => $request->query->get('date_to'),
                            'format' => 'YYYY-MM-DD',
                        ),
                    ),
                );
            }*/

            $result = $this->searchManager->query('GET', $path, $body);

            $data['entries'] = [];

            if(isset($result['hits']['hits']) == 1) {
                $index = 0;
                foreach($result['hits']['hits'] as $hit) {
                    if($type == 'feed') {
                        $feed = $this->get('readerself_core_manager_feed')->getOne(['id' => $hit['_id']]);
                        if($feed) {
                            $actions = $this->get('readerself_core_manager_action')->actionFeedMemberManager->getList(['member' => $memberConnected, 'feed' => $feed])->getResult();

                            $categories = [];
                            foreach($this->categoryManager->feedCategoryManager->getList(['member' => $memberConnected, 'feed' => $feed])->getResult() as $feedCategory) {
                                $categories[] = $feedCategory->toArray();
                            }

                            $data['entries'][$index] = $feed->toArray();
                            $data['entries'][$index]['score'] = $hit['_score'];
                            foreach($actions as $action) {
                                $data['entries'][$index][$action->getAction()->getTitle()] = true;
                            }
                            $data['entries'][$index]['categories'] = $categories;

                            $index++;
                        } else {
                            $action = 'DELETE';
                            $path = '/'.$this->searchManager->getIndex().'/feed/'.$hit['_id'];
                            $body = [];
                            $this->searchManager->query($action, $path, $body);
                        }
                    }

                    if($type == 'category') {
                        $category = $this->get('readerself_core_manager_category')->getOne(['id' => $hit['_id']]);
                        if($category) {
                            $actions = $this->get('readerself_core_manager_action')->actionCategoryMemberManager->getList(['member' => $memberConnected, 'category' => $category])->getResult();

                            $data['entries'][$index] = $category->toArray();
                            $data['entries'][$index]['score'] = $hit['_score'];
                            foreach($actions as $action) {
                                $data['entries'][$index][$action->getAction()->getTitle()] = true;
                            }

                            $index++;
                        } else {
                            $action = 'DELETE';
                            $path = '/'.$this->searchManager->getIndex().'/category/'.$hit['_id'];
                            $body = [];
                            $this->searchManager->query($action, $path, $body);
                        }
                    }

                    if($type == 'author') {
                        $author = $this->get('readerself_core_manager_author')->getOne(['id' => $hit['_id']]);
                        if($author) {
                            $data['entries'][$index] = $author->toArray();
                            $data['entries'][$index]['score'] = $hit['_score'];

                            $index++;
                        } else {
                            $action = 'DELETE';
                            $path = '/'.$this->searchManager->getIndex().'/author/'.$hit['_id'];
                            $body = [];
                            $this->searchManager->query($action, $path, $body);
                        }
                    }

                    if($type == 'item') {
                        $item = $this->itemManager->getOne(['id' => $hit['_id']]);
                        if($item) {
                            $actions = $this->actionManager->actionItemMemberManager->getList(['member' => $memberConnected, 'item' => $item])->getResult();

                            $categories = [];
                            foreach($this->categoryManager->itemCategoryManager->getList(['member' => $memberConnected, 'item' => $item])->getResult() as $itemCategory) {
                                $categories[] = $itemCategory->toArray();
                            }

                            $data['entries'][$index] = $item->toArray();
                            $data['entries'][$index]['score'] = $hit['_score'];
                            foreach($actions as $action) {
                                $data['entries'][$index][$action->getAction()->getTitle()] = true;
                            }
                            $data['entries'][$index]['categories'] = $categories;
                            $data['entries'][$index]['enclosures'] = [];

                            $data['entries'][$index]['content'] = $this->itemManager->cleanContent($item->getContent());

                            $index++;
                        } else {
                            $action = 'DELETE';
                            $path = '/'.$this->searchManager->getIndex().'/item/'.$hit['_id'];
                            $body = [];
                            $this->searchManager->query($action, $path, $body);
                        }
                    }
                }

                $data['entries_entity'] = $type;
                $data['entries_total'] = $result['hits']['total'];
                $data['entries_pages'] = $pages = ceil($result['hits']['total']/20);
                $data['entries_page_current'] = $page;
                $pagePrevious = $page - 1;
                if($pagePrevious >= 1) {
                    $data['entries_page_previous'] = $pagePrevious;
                }
                $pageNext = $page + 1;
                if($pageNext <= $pages) {
                    $data['entries_page_next'] = $pageNext;
                }

                $pagination = [];
                if($result['hits']['total'] > $size) {
                    $total = $result['hits']['total'] - 1;
                    $start = 1;
                    for($i=0;$i<=$total;$i = $i + $size) {
                        $pagination[$start] = $i;
                        $start++;
                    }
                    $data['current_from'] = intval($from);
                }
                $data['pagination'] =  $pagination;
            }
        }

        return new JsonResponse($data);
    }
}
