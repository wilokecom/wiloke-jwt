<?php

#namespace WilokeTest;

class PostSkeleton
{
	private static $_self                  = null;
	private        $aPluck;
	private        $post;
	private        $aResponse              = [];
	private        $aCurrentResponseInLoop = [];
	private        $aAdditionalArgs
	                                       = [
			'thumbnail_size' => 'large',
			'avatar_size'    => '100x100'
		];

	public static function init(\WP_Post $post): PostSkeleton
	{
		if (!self::$_self) {
			self::$_self = new PostSkeleton();
		}

		self::$_self->post = $post;
		self::$_self->aResponse = [];

		return self::$_self;
	}

	/**
	 * @param $aPluck
	 * @return PostSkeleton
	 * @throws \Exception
	 */
	public function setPluck($pluck): PostSkeleton
	{
		if (is_array($pluck)) {
			$this->aPluck = $pluck;
		} else {
			$this->aPluck = explode(',', $pluck);
		}

		return $this;
	}

	public function setAdditionalArgs(array $aAdditionalArgs): PostSkeleton
	{
		$this->aAdditionalArgs = wp_parse_args($aAdditionalArgs, $this->aAdditionalArgs);
		return $this;
	}

	public function getTitle(): ?string
	{
		return get_the_title($this->post->ID);
	}

	public function getContent(): ?string
	{
		return do_shortcode(get_post_field('post_content', $this->post->ID));
	}

	public function getItem($pluck): array
	{
		if (is_array($pluck)) {
			array_map([$this, 'getItem'], $pluck);
		} else {
			$pluck = trim($pluck);
			$method = StringHelper::makeFunc($pluck);
			if (method_exists(self::$_self, $method)) {
				$this->aCurrentResponseInLoop[$pluck] = $this->{$method}();
			} else {
				throw new \Exception(sprintf(esc_html__('The method %s does not exist',
					'wilcity-shortcode2'),
					$method));
			}
		}

		return $this->aCurrentResponseInLoop;
	}

	public function getThumbnail(): string
	{
		$url = get_the_post_thumbnail_url($this->post->ID, $this->aAdditionalArgs['thumbnail_size']);

		return empty($url) ? \WilokeThemeOptions::getThumbnailUrl('listing_featured_image') : $url;
	}

	public function getAuthor(): array
	{
		return [
			'ID'          => abs($this->post->post_author),
			'displayName' => \WilokeListingTools\Frontend\User::getField('display_name', $this->post->post_author),
			'avatar'      => get_avatar_url($this->post->post_author, $this->aAdditionalArgs['avatar_size']),
		];
	}

	public function getID(): int
	{
		return abs($this->post->ID);
	}

	/**
	 * @return array
	 * @throws \Exception
	 */
	public function get(): array
	{
		foreach ($this->aPluck as $key => $val) {
			$this->aCurrentResponseInLoop = [];

			if (is_array($val)) {
				$this->aResponse[$key] = $this->getItem($val);
			} else {
				$this->aResponse = array_merge($this->aResponse, $this->getItem($val));
			}
		}

		return $this->aResponse;
	}
}
