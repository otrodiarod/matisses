{*
* 2007-2014 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2014 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}


<div id="index" >
	<div id="slider" class="slider cf">
    	{hook h="displayMatSlider"}
        {hook h="displayMatAdvertisingHome"}
    </div>
    <div id="viewed-products" class="viewed-products">
		<div class="container">
    		{hook h="displayMatShowesProducts"}
		</div>
    </div>
    <div id="new-products" class="new-products">
		<div class="container">
    		{hook h="displayMatNewProducts"}
		</div>
	</div>
	<div class="destacados">
		<div class="container">
			<div class="btn-title cf">
				<h1><a href="#">{l s='Destacados'}</a></h1>
				<div class="btn-view-products">
					<a href="#" title="Ver todos los destacados" class="btn btn-default button button-small">Ver todos</a>
				</div>
			</div>
			<div class="info">
				<img src="/matisses/themes/matisses/img/destacados.jpg" alt="destacados">
				<div class="mask">
					<h1>Espacio para Chaordic</h1>
				</div>
			</div>
		</div>
    </div>

    <div class="experiences">
		<div class="container">
			<div class="btn-title cf">
				<h1><a href="#">{l s='Experiencias'}</a></h1>
				<div class="btn-view-products">
					<a href="#" title="Ver todos los destacados" class="btn btn-default button button-small">Ver todos</a>
				</div>
			</div>
			<div class="slide-experiencias cf">
				<ul>
					<li>
						<div class="slide-left grid_6 alpha">
							<figure>
								<img src="/matisses/themes/matisses/img/exp-01.jpg" alt="">
								<figcaption class="cf">
									<h2>Sky soul</h2>
									<button class="btn btn-default buy">Ver más</button>
								</figcaption>
							</figure>
						</div>
						<div class="slide-right grid_6 omega">
							<figure>
								<img src="/matisses/themes/matisses/img/exp-02.jpg" alt="">
								<figcaption class="cf">
									<h2>Over view</h2>
									<button class="btn btn-default buy">Ver más</button>
								</figcaption>
							</figure>
						</div>
						<div class="slide-right grid_6 alpha">
							<figure>
								<img src="/matisses/themes/matisses/img/exp-03.jpg" alt="">
								<figcaption class="cf">
									<h2>Cold mountain</h2>
									<button class="btn btn-default buy">Ver más</button>
								</figcaption>
							</figure>
						</div>
						<div class="slide-right grid_6 omega">
							<figure>
								<img src="/matisses/themes/matisses/img/exp-04.jpg" alt="">
								<figcaption class="cf">
									<h2>Gold line</h2>
									<button class="btn btn-default buy">Ver más</button>
								</figcaption>
							</figure>
						</div>
					</li>
				</ul>
			</div>
		</div>
    </div>

    <div class="zona-blog">
		<div class="container">
			{hook h="displayMatBlog"}
		</div>
	</div>
</div>


{if $page_name =='index' && false}
<div id="slider_row">
  <div id="top_column" class="center_column ">
    <!-- hook displayTopColumn -->
    {*hook h="displayTopColumn"*}
    <!-- end hook displayTopColumn -->
    <!-- hook displayEasyCarousel2 -->
    {*hook h='displayEasyCarousel2'*}
    <!-- end hook displayEasyCarousel2 -->
    <!-- hook dislayCustomBanners2 -->
    {*hook h='displayCustomBanners2'*}
    <!-- end hook dislayCustomBanners2 -->
  </div>
</div>
{/if}


{if false}
{if isset($HOOK_HOME_TAB_CONTENT) && $HOOK_HOME_TAB_CONTENT|trim}
	<div class="wrap_tabs_main">

		    	<h2 class="title_main_section"><span>{l s='Featured products'}</span></h2>
		    	<h3 class="undertitle_main">
		    		{l s='Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. '}
		    	</h3>
		<div class="tabs_main clearfix">
			{if isset($HOOK_HOME_TAB) && $HOOK_HOME_TAB|trim}

			        <ul id="home-page-tabs" class="tabs_carousel nav nav-tabs clearfix">
						{$HOOK_HOME_TAB}
					</ul>
			{/if}
			<div class="tab-content clearfix">
				{$HOOK_HOME_TAB_CONTENT}
			</div>
	    </div>
	</div>
{/if}
{if isset($HOOK_HOME) && $HOOK_HOME|trim}
	<div class="clearfix">
		{$HOOK_HOME}
	</div>
{/if}
	<!-- hook displayHomeCustom -->
	<div class="clearfix">
		{hook h='displayHomeCustom'}
	</div>
<!-- end hook displayHomeCustom -->
		<!-- hook displayEasyCarousel1 -->
		{hook h='displayEasyCarousel1'}
<!-- end hook displayEasyCarousel1 -->
{/if}
