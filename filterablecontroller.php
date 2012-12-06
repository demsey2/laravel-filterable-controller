<?php

class FilterableController extends Controller
{
	/**provide functionality for method filters**/
	public function make_method_filters($methods)
	{
		is_array($methods) or $methods = (array) $methods;

		$self = $this;

		foreach($methods as $method) {
			if( array_key_exists($this->method_filter_id($method), Filter::$filters) )
				continue;

			Filter::register($this->method_filter_id($method), function ($params) use ($self, $method) {
				return $self->$method($params);
			});
		}
	}

	public function method_filter_id($method)
	{
		return get_called_class() . '@' . $method;
	}

	public function method_filter_ids(array $methods)
	{
		$self = $this;
		return array_map(function ($m) use ($self) { return $self->method_filter_id($m); }, $methods);
	}

	public function method_filter($event, $filters)
	{
		$methods = explode('|', $filters);
		$this->make_method_filters($methods);
		
		return $this->filter($event, implode('|', $this->method_filter_ids($methods)));
	}

	/****Get method params for filters as assoc array**/
	protected function method_params_assoc($method, $parameters)
	{
		$parameters = array_values($parameters);

		if ($this->restful)
			$action = strtolower(Request::method()).'_'.$method;
		else
			$action = "action_{$method}";

		$r = new ReflectionMethod(get_called_class(), $action);
		
		if(! $params = $r->getParameters() )
			return array();

		$data = array();

		foreach($params as $i => $p) {
			$key = $p->getName();
			$val = isset($parameters[$i]) ?
					$parameters[$i]
				 : ($p->isDefaultValueAvailable() ? $p->getDefaultValue() : null);

			$data[$key] = $val;
		}

		return $data;
	}

	/**
	 * Execute a controller method with the given parameters.
	 * Method filters get the arguments of the method they are filtering
	 * passed to them in the same order.
	 *
	 * @param  string    $method
	 * @param  array     $parameters
	 * @return Response
	 */
	public function execute($method, $parameters = array())
	{
		$filters = $this->filters('before', $method);

		// Again, as was the case with route closures, if the controller "before"
		// filters return a response, it will be considered the response to the
		// request and the controller method will not be used.
		// The parameters of the controller action will get passed to the filter
		// in the form of an assoc array.

		$params = $this->method_params_assoc($method, $parameters);
		$params['action'] = $method;

		$response = Filter::run($filters, array($params), true);

		if (is_null($response))
		{
			$this->before();

			$response = $this->response($method, $parameters);
		}

		$response = Response::prepare($response);

		// The "after" function on the controller is simply a convenient hook
		// so the developer can work on the response before it's returned to
		// the browser. This is useful for templating, etc.
		$this->after($response);

		Filter::run($this->filters('after', $method), array($response, $params));

		return $response;
	}
}