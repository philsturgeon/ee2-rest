# Rest

Integrate RESTful API’s into your ExpressionEngine 2.0 website with this Rest module. You can list Tweets, search for Digg articles, show off Flickr photographs, search YouTube for videos and interact with any open RESTful API.

## Installation

Move the "rest/" folder inside "system/expressionengine/third_party/".

## Usage

You can create REST requests in the backend module and reference them with Template Syntax, then loop through the returned response. Everything sent back from the API is converted into a loopable ExpressionEngine array wether its XML, JSON, Serialized data, pure PHP, CSV, RSS and Atom feeds. It will even include XML attributes as well as node values so no data is lost.

	<ul>
	{exp:rest name="foo”}
	<li>{some_value}</li>
	{/exp:rest}
	</ul>

Rest module can do more than just grab information from predefined requests. You can create REST requests on the fly:

	{exp:rest url="http://example.com/” verb="get” format="json” param:foo="bar”}
	{some_value}<br/>
	{/exp:rest}

## Parameters

* name - Name of the saved request

* id - ID of the saved request

* record_type - Single or Multiple records? 
	* Default: "m"
	* Options: "s" or "m"

* base - By default you will loop through the top level of the data. Use this to loop through nested arrays.
	* Default: _empty_
	* Example: data,links

* limit - Number of results to loop through inside the base element.
	* Default: _all_	

* offset - Where to start looping 
	* Default: _all_

* format - What format should the content be encoded in? This sets the Accept HTTP header
	* Default: "xml”
	* Options: "xml", "atom", "rss", "json", "serialized" or "csv"

* verb - Also alias "method", picks which HTTP method to use.
	* Default: "get"
	* Options: "get", "post", "put" or "delete"

Parameters can be added even if you call a saved request, it will merge or override params saved in the CP.

## Debugging

To debug a request you can add `debug="yes”` to the tag.

You could use this syntax with other Template Tags and create some very dynamic flexible sites.

## Pagination 

	{exp:rest url="[some url]" format="xml" limit="16" offset="{global:pagination_offset}" paginate="bottom" base="feed,documents,document"}
	    
	    <p class="largey"><a href="{url}" title="{title}">{title}</a><span class="large right">{createDate_mon}/{createDate_day}/{createDate_year}</span></p>
	 
	    {paginate}
	 	   <ul class="pagination margin_bottom_04">      
			{previous_page}
			    <li><a href="{pagination_url}" title="Previous" class="arrow-prev pager-element">Previous</a></li>
			{/previous_page}

			{page}
				<li><a href="{pagination_url}"class="current">{pagination_page_number}</a></li>
			{/page}

			{next_page}
				<li><a href="{pagination_url}" title="Next" class="arrow-next pager-element">Next</a></li>
			{/next_page}
			</ul>
	    {/paginate}

	{/exp:rest} 