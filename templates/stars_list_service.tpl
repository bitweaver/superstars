{strip}
{if $serviceHash.stars_pixels}
	<ul class="stars-rating">
		<li class="stars-current" style="width:{$serviceHash.stars_pixels}px;"></li>
	</ul>
{else}
	{tr}Not Rated{/tr}
{/if}
{/strip}
