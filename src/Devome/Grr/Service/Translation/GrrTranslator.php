<?php

namespace Devome\Grr\Service\Translation;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Traducteur de GRR : décore le traducteur Symfony (service « translator ») pour que
 * $translator->trans('cle') et {{ 'cle'|trans }} donnent exactement le texte attendu par GRR.
 *
 * Par rapport au traducteur Symfony seul, il ajoute pour le domaine « messages » :
 *  - la langue de l'utilisateur (session, puis langue par défaut du site) quand aucune locale n'est précisée ;
 *  - les variables %dbDb%, %dbsys% et %pass_leng% ;
 *  - les textes personnalisés, prioritaires sur les catalogues : réglages « trad_<clé> »,
 *    fichiers personnalisation/langue/lang_subst*.php et vocabulaire des modules (hook « vocab »).
 *
 * C'est l'implémentation de l'ancienne fonction get_vocab(), qui ne fait plus que l'appeler.
 */
#[AsDecorator(decorates: 'translator')]
final class GrrTranslator implements TranslatorInterface, TranslatorBagInterface, LocaleAwareInterface
{
    private const DOMAIN = 'messages';

    private bool $moduleVocabularyLoaded = false;

    public function __construct(
        #[AutowireDecorated]
        private readonly TranslatorInterface&TranslatorBagInterface&LocaleAwareInterface $inner,
    ) {
    }

    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        if ($domain !== null && $domain !== self::DOMAIN) {
            return $this->inner->trans($id, $parameters, $domain, $locale);
        }

        $userLocale = $this->userLocale();
        $locale ??= $userLocale;
        $translation = $this->inner->trans($id, $parameters + $this->defaultParameters(), self::DOMAIN, $locale);

        if ($locale === $userLocale) {
            return $this->customText($id, $translation, $parameters === []) ?? $translation;
        }

        return $translation;
    }

    public function getLocale(): string
    {
        return $this->inner->getLocale();
    }

    public function setLocale(string $locale): void
    {
        $this->inner->setLocale($locale);
    }

    public function getCatalogue(?string $locale = null): MessageCatalogueInterface
    {
        return $this->inner->getCatalogue($locale);
    }

    public function getCatalogues(): array
    {
        return $this->inner->getCatalogues();
    }

    /** Langue des catalogues GRR pour l'utilisateur courant (ex. « fr-fr »). */
    private function userLocale(): string
    {
        if (\function_exists('grr_translation_locale')) {
            return grr_translation_locale(\is_string($GLOBALS['locale'] ?? null) ? $GLOBALS['locale'] : null);
        }

        return $this->inner->getLocale();
    }

    /** @return array<string, string> */
    private function defaultParameters(): array
    {
        return [
            '%dbDb%' => (string) ($GLOBALS['dbDb'] ?? ''),
            '%dbsys%' => (string) ($GLOBALS['dbsys'] ?? ''),
            '%pass_leng%' => (string) ($GLOBALS['pass_leng'] ?? ''),
        ];
    }

    /**
     * Texte personnalisé à utiliser à la place de la traduction du catalogue, ou null.
     * $GLOBALS['vocab'] est le vocabulaire chargé par include/language.inc.php : catalogue de la langue
     * de l'utilisateur, modifié par les fichiers de personnalisation.
     */
    private function customText(string $id, string $translation, bool $withoutParameters): ?string
    {
        if (!\class_exists('Settings', false) || !\is_array($GLOBALS['vocab'] ?? null)) {
            return null;
        }

        if (!$this->moduleVocabularyLoaded && \class_exists('Hook', false)) {
            $this->moduleVocabularyLoaded = true;
            $moduleVocabulary = \Hook::Appel('vocab');
            if (\is_array($moduleVocabulary['vocab'] ?? null)) {
                $GLOBALS['vocab'] = array_merge($GLOBALS['vocab'], $moduleVocabulary['vocab']);
            }
        }

        if (!isset($GLOBALS['vocab'][$id])) {
            return null;
        }

        $setting = \Settings::get('trad_'.$id);
        if ($setting != '') {
            return (string) $setting;
        }

        $legacy = (string) $GLOBALS['vocab'][$id];
        if ($translation === $id) {
            return $legacy;     // clé fournie uniquement par un module ou un fichier de personnalisation
        }

        return ($withoutParameters && $legacy !== $translation) ? $legacy : null;
    }
}
