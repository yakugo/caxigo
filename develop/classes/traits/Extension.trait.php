<?php

namespace SocialTrait;

trait Extension {
	private $extended_data;

	protected function invoke() {
		$args = func_get_args();
		$args_num = func_num_args();
		$endarg_num = $args_num - 1;
		$endarg = $args[$endarg_num];

		unset($args[$endarg_num]);

		$args = implode(', ', $args);

		$this->extended_data = \SocialKit\Plugins::invoke($args, $endarg);
		return $this->extended_data;
	}
}