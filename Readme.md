# monetivo PrestaShop 1.6-1.7 Plugin

## Wstęp

To repozytorium zawiera kod moduły płatności monetivo dla PrestaShop w wersji 1.6 i 1.7. 
Aby zainstalować moduł skorzystaj z poniższej instrukcji.
Jeżeli jestes developerem i chciałbyś pomóc (super!) to serdecznie zapraszamy! 

## Wymagania i zależności

- PrestaShop w wersji **1.6** lub wyższej
- konto Merchanta w monetivo ([załóż konto](https://merchant.monetivo.com/register))

Moduł korzysta z naszego [klienta PHP](https://github.com/monetivo/monetivo-php) zatem wymagania środowiska są tożsame, tj. PHP w wersji 5.5 lub wyższej.
Dodatkowo potrzebne są moduły PHP:
- [`curl`](https://secure.php.net/manual/en/book.curl.php),
- [`json`](https://secure.php.net/manual/en/book.json.php)

## Instalacja

1. [Pobierz archiwum ZIP](https://merchant.monetivo.com/download/monetivo-prestashop.zip) z wtyczką na dysk.
2. Przejdź do podstrony „Ulepszenia > Moduły > Wybrane", a następnie z wybierz przycisk **Załaduj moduł**.
3. Nowo zainstalowany moduł pojawi się na liście.

## Konfiguracja
1.	Przejdź do panelu administracyjnego i z sekcji „Ulepszenia" otwórz zakładkę „Moduły > Wybrane”.
2.	Kliknij „Configure” (konfiguruj) przy pozycji „monetivo” by przejść do ustawień bramki.
3.	Skonfiguruj bramkę podając dane uzyskane w Panelu Merchanta:
   - Wpisz dane: Login użytkownika, Hasło oraz Token aplikacji.
4.  Kliknij „Save” (zapisz). Wtyczka spróbuje nawiązać połączenie z systemem monetivo weryfikując tym samym poprawność wpisanych danych.

## Changelog

1.0.0 2017-06-26

- Wersja stabilna

## Błędy

Jeżeli znajdziesz jakieś błędy zgłoś je proszę przez GitHub. Zachęcamy również do zgłaszania pomysłów dotyczących ulepszenia naszych rozwiązań.

## Wsparcie
W razie problemów z integracją prosimy o kontakt z naszym supportem. 