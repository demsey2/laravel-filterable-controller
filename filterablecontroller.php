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

			Filter::register($this->method_filter_id($method), function () use ($self, $method) {
				return $self->$method();
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

	public function method_filters($methods)
	{
		is_array($methods) or $methods = (array) $methods;
		$this->make_method_filters($methods);

		return implode('|', $this->method_filter_ids($methods));
	}

	public function before_method_filter($filters)
	{
		return $this->filter('before', $this->method_filters($filters));
	}

	public function after_method_filter($filters)
	{
		return $this->filter('after', $this->method_filters($filters));
	}

	/**********/

}