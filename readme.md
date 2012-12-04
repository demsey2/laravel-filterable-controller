FilterableController allows controllers to register their own methods as filters.

Credit for the idea goes to machuga. The implementation is my own.

This helps with specially with ACL as a controller specific filters can exist in that 
specific controller only and will be registered only when that controller is used.

You need to keep the methods public (thats a limitation of php 5.3) and you can easily use
$this in the methods.

When using filterable controller, the before filters get passed the arguments of the current action and
the current action as an assoc array. The same assoc array then is passed to the after filter as the 
second argument (after response).

For example, when a filter is applied to the action:
	
	public function get_show($section, $page = 'index')
	{
		...
	}

The following assoc array gets passed as argument to filters:
	
	array(
		'section' => $section,
		'page' => $page,
		'action' => 'show'
	)

Full Usage:

	//routes.php
	Route::controller('items');

	//controllers/items.php
	class Items_Controller extends Controller
	{
		public $restful = true;

		public function __construct()
		{
			$this->filter('before', 'auth');
			$this->method_filter('before', 'user_active_check|role_check');
			$this->method_filter('before', 'add_creator_to_input')->only(array('create', 'update'));
			$this->method_filer('before', 'user_can_delete_item')->only('destroy');
		}

		public function user_active_check()
		{
			if(! Auth::user()->is_active )
				return Response::error(404);
		}

		public function role_check()
		{
			if(! Auth::user()->has_role('items-manager') )
				return Response::error(404);
		}

		public function user_can_delete_item($params)
		{
			if(! Auth::user()->can_delete_item($params['id']) )
				return Response::error(404);
		}

		public function log_activity($response)
		{
			ItemsManagerLogEntry::create(array(
				'user_id' => Auth::user()->id, 'uri' => URI::current(), 'method' => Request::method()
			));
		}

		public function add_creator_to_input()
		{
			Input::merge(array('creator_id' => Auth::user()->id));
		}

		/**actions**/
		* Imagine you have your standard RESTful actions over here
		* get_index(), get_show($id), get_new(), post_create(), get_edit($id), put_update($id), delete_destroy($id)
		*/
	}