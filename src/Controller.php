<?php namespace Barryvdh\TranslationManager;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Barryvdh\TranslationManager\Models\Translation;
use Illuminate\Support\Collection;

use Stichoza\GoogleTranslate\TranslateClient;

use Vinfo\Language;
use Cache;
use Lang;
use Input;

class Controller extends BaseController
{
	/** @var \Barryvdh\TranslationManager\Manager  */
	protected $manager;

	public function __construct(Manager $manager)
	{
		$this->manager = $manager;
	}

	public function getIndex($group = null)
	{
		$group = str_replace('.', '/', $group);
		$locales = $this->loadLocales();
		$groups = Translation::groupBy('group');
		$excludedGroups = $this->manager->getConfig('exclude_groups');
		if($excludedGroups){
			$groups->whereNotIn('group', $excludedGroups);
		}

		$groups = $groups->lists('group', 'group');
		if ($groups instanceof Collection) {
			$groups = $groups->all();
		}
		$groups = [''=>'Choose a group'] + $groups;
		$numChanged = Translation::where('group', $group)->where('status', Translation::STATUS_CHANGED)->count();

		$allTranslations = Translation::where('group', $group)->orderBy('key', 'asc')->get();
		$numTranslated = count($allTranslations);
		$numKeys = count(Translation::where('group', $group)->select('key')->groupBy('key')->get());
		$numTranslations = $numKeys * count($locales);
		$translations = [];
		foreach($allTranslations as $translation){
			$translations[$translation->key][$translation->locale] = $translation;
		}

		$show_locales = (array) Cache::get('translations.show_locales', $locales);

		return view('translation-manager::index')
			->with('translations', $translations)
			->with('locales', $locales)
			->with('show_locales', $show_locales)
			->with('groups', $groups)
			->with('group', $group)
			->with('numKeys', $numKeys)
			->with('numTranslations', $numTranslations)
			->with('numTranslated', $numTranslated)
			->with('numChanged', $numChanged)
			->with('editUrl', action('\Barryvdh\TranslationManager\Controller@postEdit', [str_replace('/','.',$group)]))
			->with('deleteEnabled', $this->manager->getConfig('delete_enabled'));
	}

	public function getView($group)
	{
		return $this->getIndex($group);
	}

	protected function loadLocales()
	{
		//Set the default locale as the first one.
		$locales = Translation::groupBy('locale')->lists('locale');
		if ($locales instanceof Collection) {
			$locales = $locales->all();
		}
		$langs = Language::lists('code');
		if ($langs instanceof Collection) {
			$langs = $langs->all();
		}
		$locales = array_merge($locales, $langs);
		sort($locales);
		$locales = array_merge([config('app.locale')], $locales);
		return array_unique($locales);
	}

	public function postLocales()
	{
		Cache::put('translations.show_locales', Input::get('show_locales'), 30);
		return array('status' => 'ok');
	}

	public function postAdd(Request $request, $group)
	{
		$group = str_replace('.', '/', $group);
		$keys = explode("\n", $request->get('keys'));

		foreach($keys as $key){
			$key = trim($key);
			if($group && $key){
				$this->manager->missingKey('*', $group, $key);
			}
		}
		return redirect()->back();
	}

	public function postEdit(Request $request, $group)
	{
		$group = str_replace('.', '/', $group);
		if(!in_array($group, $this->manager->getConfig('exclude_groups'))) {
			$name = $request->get('name');
			$value = $request->get('value');

			list($locale, $key) = explode('|', $name, 2);
			$translation = Translation::firstOrNew([
				'locale' => $locale,
				'group' => $group,
				'key' => $key,
			]);
			$translation->value = (string) $value ?: null;
			$translation->status = Translation::STATUS_CHANGED;
			$translation->save();
			return array('status' => 'ok');
		}
	}

	public function postDelete($group, $key)
	{
		$group = str_replace('.', '/', $group);
		if(!in_array($group, $this->manager->getConfig('exclude_groups')) && $this->manager->getConfig('delete_enabled')) {
			Translation::where('group', $group)->where('key', $key)->delete();
			return ['status' => 'ok'];
		}
	}

	public function postImport(Request $request)
	{
		$replace = $request->get('replace', false);
		$counter = $this->manager->importTranslations($replace);

		return ['status' => 'ok', 'counter' => $counter];
	}

	public function postFind()
	{
		$numFound = $this->manager->findTranslations();

		return ['status' => 'ok', 'counter' => (int) $numFound];
	}

	public function postPublish($group)
	{
		$group = str_replace('.', '/', $group);
		$this->manager->exportTranslations($group);

		return ['status' => 'ok'];
	}

	public function postTranslate()
	{
		$group = Input::get('group');
		$key = Input::get('key');
		$lang = Input::get('lang');

		$key = str_replace('.','/',$group).'.'.$key;

		if (Lang::has($key, 'en'))
		{
			$word = Lang::get($key, [], 'en');

			if ($translated = Cache::get($word.':'.$lang)) {
				// Cached
			}
			else {
				$translated = TranslateClient::translate('en', $lang, $word);
				Cache::put($word.':'.$lang, $translated, 30);
			}
		}
		else {
			$translated = null;
		}

		$translated = sentence_case($translated);
		$translated = str_replace([' .', '\''], ['.', '’'], $translated);

		if (stripos($lang, 'fr') === 0) {
			$translated = preg_replace('/\s+\?/', '&nbsp;?', $translated);
		}

		return ['word' => $translated];
	}
}
