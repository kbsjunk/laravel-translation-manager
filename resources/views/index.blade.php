@extends('layouts/default')

@section('title', 'Translations')
@section('subtitle', $group)

@section('content')

<p>Warning, translations are not visible until they are exported back to the app/lang file, using the <tt>php artisan translation:export</tt> command or publish button.</p>
<div class="alert alert-success success-import" style="display:none;">
	<p>Done importing, processed <strong class="counter">N</strong> items! Reload this page to refresh the groups!</p>
</div>
<div class="alert alert-success success-find" style="display:none;">
	<p>Done searching for translations, found <strong class="counter">N</strong> items!</p>
</div>
<div class="alert alert-success success-publish" style="display:none;">
	<p>Done publishing the translations for group '{{ $group }}'!</p>
</div>
@if(Session::has('successPublish'))
<div class="alert alert-info">
	{{ Session::get('successPublish') }}
</div>
@endif

@if(empty($group))
<form class="form-inline form-import" method="POST" action="{{ action('\Barryvdh\TranslationManager\Controller@postImport') }}" data-remote="true" role="form">
	<input type="hidden" name="_token" value="{{ csrf_token() }}">
	<select name="replace" class="form-control">
		<option value="0">Append new translations</option>
		<option value="1">Replace existing translations</option>
	</select>
	<button type="submit" class="btn btn-success"  data-disable-with="Loading..">Import groups</button>
</form>
<form class="form-inline form-find" method="POST" action="{{ action('\Barryvdh\TranslationManager\Controller@postFind') }}" data-remote="true" role="form" data-confirm="Are you sure you want to scan you app folder? All found translation keys will be added to the database.">
	<input type="hidden" name="_token" value="{{ csrf_token() }}">
	<button type="submit" class="btn btn-info" data-disable-with="Searching.." >Find translations in files</button>
</form>
@endif

<div class="panel panel-default">
	<div class="panel-body">
		<div class="row">
			<div class="col-md-6">
				<legend>
					Translation Groups
				</legend>
				<form role="form">
					<input type="hidden" name="_token" value="{{ csrf_token() }}">
					<div class="form-group">
						<select name="group" id="group" class="form-control group-select">
							@foreach($groups as $key => $value)
							<option value="{{ str_replace('/','.',$key) }}"{{ $key == $group ? ' selected':'' }}>{{ $value }}</option>
							@endforeach
						</select>
					</div>
				</form>
				<div class="panel-footer">
					@if(!empty($group))
					<form class="form-inline form-publish" method="POST" action="{{ action('\Barryvdh\TranslationManager\Controller@postPublish', str_replace('/','.',$group)) }}" data-remote="true" role="form" data-confirm="Are you sure you want to publish the translations group '{{ $group }}? This will overwrite existing language files.">
						<input type="hidden" name="_token" value="{{ csrf_token() }}">
						<button type="submit" class="btn btn-info" data-disable-with="Publishing.." >Publish translations</button>
					</form>
					@endif	
				</div>
			</div>
			<div class="col-md-6">
				@if($group)
				<legend>
					Add Translation Keys
				</legend>
				<form action="{{ action('\Barryvdh\TranslationManager\Controller@postAdd', array(str_replace('/','.',$group))) }}" method="POST"  role="form">
					<input type="hidden" name="_token" value="{{ csrf_token() }}">
					<div class="form-group">
						<textarea class="form-control" rows="3" name="keys" placeholder="Add 1 key per line, without the group prefix"></textarea>
					</div>
					<div class="panel-footer">
						<input type="submit" value="Add keys" class="btn btn-primary">
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<h4>Total: {{ $numTranslations }}, changed: {{ $numChanged }}</h4>
<table class="table">
	<thead>
		<tr>
			<th width="15%">Key</th>
			@foreach($locales as $locale)
			<th>{{ $locale }}</th>
			@endforeach
			@if($deleteEnabled)
			<th>&nbsp;</th>
			@endif
		</tr>
	</thead>
	<tbody>

		@foreach($translations as $key => $translation)
		<tr id="{{ $key }}" data-group="{{ str_replace('/','.',$group) }}">
			<td>{{ $key }}</td>
			@foreach($locales as $locale)
			<?php $t = isset($translation[$locale]) ? $translation[$locale] : null; ?>

			<td>
				<a href="#edit" class="editable status-{{ $t ? $t->status : 0 }} locale-{{ $locale }}" data-locale="{{ $locale }}" data-name="{{ $locale . "|" . $key }}" id="username" data-type="{{ $t ? strlen($t->value) > 20 ? 'textarea' : 'text' : 'textarea' }}"  data-pk="{{ $t ? $t->id : 0 }}" data-url="{{ $editUrl }}" data-title="Enter translation">{{ $t ? htmlentities($t->value, ENT_QUOTES, 'UTF-8', false) : '' }}</a>
			</td>
			@endforeach
			@if($deleteEnabled)
			<td>
				<a href="{{ action('\Barryvdh\TranslationManager\Controller@postDelete', [str_replace('/','.',$group), $key]) }}" class="delete-key" data-confirm="Are you sure you want to delete the translations for '{{ $key }}?"><span class="glyphicon glyphicon-trash"></span></a>
			</td>
			@endif
		</tr>
		@endforeach

	</tbody>
</table>
@else
<p>Choose a group to display the group translations. If no groups are visisble, make sure you have run the migrations and imported the translations.</p>

@endif
</div>
@endsection

@section('scripts')
<script src="//cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.0/bootstrap3-editable/js/bootstrap-editable.min.js"></script>
<script src="{{ asset('vendor/jquery-ujs/rails.js') }}"></script>
<script>
	jQuery(document).ready(function($){

		$.fn.editableform.buttons = '<button type="submit" class="btn btn-xs btn-primary editable-submit">OK</button>'+
			'<button type="button" class="btn btn-xs btn-default editable-cancel">Cancel</button>'+
			'&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<button type="button" class="btn btn-xs btn-default editable-auto">Automatic</button>';


		$.ajaxSetup({
			beforeSend: function(xhr, settings) {
				console.log('beforesend');
				settings.data += "&_token={{ csrf_token() }}";
			}
		});

		$('.editable').editable({
			showbuttons: 'bottom'
		}).on('hidden', function(e, reason){
			var locale = $(this).data('locale');
			if(reason === 'save'){
				$(this).removeClass('status-0').addClass('status-1');
			}
			if(reason === 'save' || reason === 'nochange') {
				var $next = $(this).closest('tr').next().find('.editable.locale-'+locale);
				setTimeout(function() {
					$next.editable('show');
				}, 300);
			}
		});

		$('.group-select').on('change', function(){
			var group = $(this).val();
			if (group) {
				window.location.href = '{{ action('\Barryvdh\TranslationManager\Controller@getView') }}/'+$(this).val();
			} else {
				window.location.href = '{{ action('\Barryvdh\TranslationManager\Controller@getIndex')  }}';
			}
		});

		$("a.delete-key").click(function(event){
			event.preventDefault();
			var row = $(this).closest('tr');
			var url = $(this).attr('href');
			var id = row.data('group');
			$.post( url, {id: id}, function(){
				row.remove();
			} );
		});

		$('.form-import').on('ajax:success', function (e, data) {
			$('div.success-import strong.counter').text(data.counter);
			$('div.success-import').slideDown();
		});

		$('.form-find').on('ajax:success', function (e, data) {
			$('div.success-find strong.counter').text(data.counter);
			$('div.success-find').slideDown();
		});

		$('.form-publish').on('ajax:success', function (e, data) {
			$('div.success-publish').slideDown();
		});

		$('body').on('click', '.editable-auto', function(event) {

			event.preventDefault();
			var row = $(this).closest('tr');
			var url = "{{ action('ApiController@translateWord') }}";
			var group = row.data('group');
			var key = row.attr('id');
			var lang = $(this).closest('.popover').prev('.editable').data('locale');
			var input = $(this).closest('.editable-buttons').prev('.editable-input').find('input,textarea');

			$.post( url, {group: group, key: key, lang: lang}, function(data){
				$(input).val(data.word);
			} );
		});

	})
</script>
@endsection

@section('styles')
<link href="//cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.0/bootstrap3-editable/css/bootstrap-editable.css" rel="stylesheet"/>
<style>
	a.status-1{
		font-weight: bold;
	}
</style>
@endsection