@extends('layouts/default')

@section('title', 'Translations')
@section('subtitle', $group)

@section('content')
<div class="panel-unstyled">
	<p>Warning, translations are not visible until they are exported back to the app/lang file, using the <tt>php artisan translation:export</tt> command or publish button.</p>
	@if(empty($group))
	<p>Choose a group to display the group translations. If no groups are visisble, make sure you have run the migrations and imported the translations.</p>
	@endif
</div>
<div class="row">
	<div class="col-md-8 col-md-offset-2">
		<div class="alert alert-success success-import fade in" style="display:none;">
			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<p>Done importing, processed <strong class="counter">N</strong> items! Reload this page to refresh the groups!</p>
		</div>
		<div class="alert alert-success success-find fade in" style="display:none;">
			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<p>Done searching for translations, found <strong class="counter">N</strong> items!</p>
		</div>
		<div class="alert alert-success success-publish fade in" style="display:none;">
			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<p>Done publishing the translations for group '{{ $group }}'!</p>
		</div>
		@if(Session::has('successPublish'))
		<div class="alert alert-info fade in">
			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			{{ Session::get('successPublish') }}
		</div>
		@endif
	</div>
</div>

<div class="panel panel-default">
	<div class="panel-body">
		<div class="row">
			<div class="col-md-4">
				<legend>
					Translation Groups
				</legend>
				<form role="form">
					<input type="hidden" name="_token" value="{{ csrf_token() }}">
					<select name="group" id="group" class="form-control group-select" data-selectize="single">
						@foreach($groups as $key => $value)
						<option value="{{ str_replace('/','.',$key) }}"{{ $key == $group ? ' selected':'' }}>{{ $value }}</option>
						@endforeach
					</select>
				</form>
				@if(!empty($group))
				<br>
				<table class="table table-condensed">
					<tbody>
						<tr>
							<th class="col-sm-10">Keys</th> <td class="col-sm-2 text-center">{{ $numKeys }}</td>
						</tr>
						<tr>
							<th>Locales</th> <td class="text-center">{{ count($locales) }}</td>
						</tr>
						<tr>
							<th>Translations</th> <td class="text-center">{{ $numTranslations }}</td>
						</tr>
						<tr>
							<th>Translated</th> <td class="text-center">{{ $numTranslated }}</td>
						</tr>
						<tr>
							<th>Changed/ Unpublished</th> <td class="text-center"><strong>{{ $numChanged }}</strong></td>
						</tr>
					</tbody>
				</table>
				<div class="panel-footer">
					<form class="form-inline form-publish" method="POST" action="{{ action('\Barryvdh\TranslationManager\Controller@postPublish', str_replace('/','.',$group)) }}" data-remote="true" role="form" data-confirm="Are you sure you want to publish the translations group '{{ $group }}? This will overwrite existing language files.">
						<input type="hidden" name="_token" value="{{ csrf_token() }}">
						<button type="submit" class="btn btn-primary" data-disable-with="Publishing.." >Publish translations</button>
						<a href="{{ action('\Barryvdh\TranslationManager\Controller@getIndex') }}" class="btn btn-default">Import</a>
					</form>
				</div>
				@endif
			</div>
			@if($group)
			<div class="col-md-6">
				<br class="hidden-md hidden-lg">
				<form action="{{ action('\Barryvdh\TranslationManager\Controller@postAdd', array(str_replace('/','.',$group))) }}" method="POST"  role="form">
					<legend>
						Translation Keys
					</legend>
					<input type="hidden" name="_token" value="{{ csrf_token() }}">
					<textarea class="form-control input-sm" rows="4" name="keys" placeholder="Add 1 key per line, without the group prefix"></textarea>
					<div class="panel-footer unstyled">
						<input type="submit" value="Add keys" class="btn btn-default">
					</div>
				</form>
			</div>
			<div class="col-md-2">
				<form class="form-show-locales" method="POST" action="{{ action('\Barryvdh\TranslationManager\Controller@postLocales') }}" data-remote="true" role="form">
					<br class="hidden-md hidden-lg">
					<legend>
						Languages
					</legend>
					<input type="hidden" name="_token" value="{{ csrf_token() }}">
					@foreach($locales as $locale)
					<div class="checkbox">
						<label>
							<input type="checkbox" name="show_locales[]" class="checkbox-sm" value="{{ $locale }}" data-hide-locale{{ in_array($locale, $show_locales) ? ' checked' : null }}>
							<span>{{ sentence_case(Punic\Language::getName($locale, $locale)) }}</span>
						</label>
					</div>
					@endforeach
					<div class="panel-footer">
						<a href="{{ action('LanguagesController@create') }}" class="btn btn-default">Add languages</a>
					</div>
				</form>
			</div>
			@else
			<div class="col-md-8">
				<br class="hidden-md hidden-lg">
				<legend>
					Import Translation Keys
				</legend>
				<div class="row">
					<div class="col-md-9">
						<form class="form-import" method="POST" action="{{ action('\Barryvdh\TranslationManager\Controller@postImport') }}" data-remote="true" role="form">
							<div class="row">
								<div class="col-md-8">
									<input type="hidden" name="_token" value="{{ csrf_token() }}">
									<select name="replace" class="form-control" data-selectize="single">
										<option value="0">Append new translations</option>
										<option value="1">Replace existing translations</option>
									</select>
								</div>
								<div class="col-md-4">
									<button type="submit" class="btn btn-default btn-block" data-disable-with="Loading..">From lang files</button>
								</div>
							</div>
						</form>
					</div>
					<div class="col-md-3">
						<form class="form-inline form-find" method="POST" action="{{ action('\Barryvdh\TranslationManager\Controller@postFind') }}" data-remote="true" role="form" data-confirm="Are you sure you want to scan you app folder? All found translation keys will be added to the database.">
							<input type="hidden" name="_token" value="{{ csrf_token() }}">
							<button type="submit" class="btn btn-default btn-block" data-disable-with="Searching..">From view files</button>
						</form>
					</div>
				</div>
			</div>
			@endif
		</div>
	</div>
	<div class="panel-footer unstyled">

	</div>
</div>
</div><!-- .container -->
<div class="container-fluid">
	@if($group)
	<h4 class="page-header">
		Translations
	</h4>
	<div class="panel panel-default">
		<div class="table-responsive">
			<table class="table table-bordered">
				<thead>
					<tr>
						<th width="15%">Key</th>
						@foreach($locales as $locale)
						<th data-locale-column="{{ $locale }}"{!! in_array($locale, $show_locales) ? null : ' style="display:none;"' !!}>
							<span>{{ sentence_case(Punic\Language::getName($locale, $locale)) }}</span>
						</th>
						@endforeach
						@if($deleteEnabled)
						<th width="20">&nbsp;</th>
						@endif
					</tr>
				</thead>
				<tbody>
					@foreach($translations as $key => $translation)
					<tr id="{{ $key }}" data-group="{{ str_replace('/','.',$group) }}">
						<td>{{ $key }}</td>
						@foreach($locales as $locale)
						<?php $t = isset($translation[$locale]) ? $translation[$locale] : null; ?>
						<td data-locale-column="{{ $locale }}"{!! in_array($locale, $show_locales) ? null : ' style="display:none;"' !!}>
							<a href="#edit" class="editable status-{{ $t ? $t->status : 0 }} locale-{{ $locale }}" data-locale="{{ $locale }}" data-name="{{ $locale . "|" . $key }}" id="username" data-type="{{ $t ? strlen($t->value) > 20 ? 'textarea' : 'text' : 'textarea' }}"  data-pk="{{ $t ? $t->id : 0 }}" data-url="{{ $editUrl }}" data-title="Enter translation">{{ $t ? htmlentities($t->value, ENT_QUOTES, 'UTF-8', false) : '' }}</a>
						</td>
						@endforeach
						@if($deleteEnabled)
						<td>
							<a href="{{ action('\Barryvdh\TranslationManager\Controller@postDelete', [str_replace('/','.',$group), $key]) }}" class="btn btn-link btn-sm delete-key" data-confirm="Are you sure you want to delete the translations for '{{ $key }}?"><i class="fa fa-trash"></i></a>
						</td>
						@endif
					</tr>
					@endforeach

				</tbody>
			</table>
		</div>
	</div>
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

		$("[data-hide-locale]").on('change', function() {
			$('[data-locale-column="'+$(this).val()+'"]').toggle($(this).prop('checked'));
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

		$('.form-show-locales :checkbox').on('change', function() {
			$(this).closest('form').submit();
		});

		$('body').on('click', '.editable-auto', function(event) {

			event.preventDefault();
			var row = $(this).closest('tr');
			var url = "{{ action('\Barryvdh\TranslationManager\Controller@postTranslate') }}";
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