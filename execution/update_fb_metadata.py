import json
import sys
from pathlib import Path

import requests

_root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(_root / "scripts"))
from gravity_cursor_env import require_facebook_graph_user_token  # noqa: E402

USER_ACCESS_TOKEN = require_facebook_graph_user_token()

def main():
    print("1. Pobieranie listy stron zarządzanych przez podany token (EAARO...).")
    url = f"https://graph.facebook.com/v19.0/me/accounts?access_token={USER_ACCESS_TOKEN}"
    try:
        response = requests.get(url, timeout=10)
        data = response.json()
    except Exception as e:
        print(f"Błąd HTTP: {e}")
        sys.exit(1)
        
    if "error" in data:
        print("Błąd pobierania stron:", data["error"]["message"])
        sys.exit(1)
        
    pages = data.get("data", [])
    if not pages:
        print("Brak zarządzanych stron dla tego tokenu lub token nie ma uprawnienia pages_show_list.")
        sys.exit(0)
        
    # Szukamy RS Pperformance
    target_page = None
    for page in pages:
        name = page.get("name", "")
        # Uwzględniamy obecną nazwę (nawet z literówką)
        if "RS" in name and "erformance" in name:
            target_page = page
            break
            
    if not target_page:
        print("Nie znaleziono docelowej strony. Dostępne strony:\n", [p['name'] for p in pages])
        sys.exit(0)
        
    page_id = target_page["id"]
    page_access_token = target_page["access_token"]
    
    print(f" Znaleziono stronę: {target_page['name']} (ID: {page_id})")
    print("2. Zmiana sekcji Bio (about) / Description oraz przydatnych detali.\n")
    
    # Dane ze strategii "Transparentność"
    short_bio = "RS Performance Gdańsk: Twoje auto w dobrych rękach. Najpierw weryfikacja, potem wycena, autoryzacja, naprawa. Pełna transparentność."
    long_desc = (
        "W RS Performance w Gdańsku stawiamy na transparentność i rzetelność. "
        "Nasze motto to: Najpierw weryfikacja, potem wycena, autoryzacja, naprawa. "
        "Wiedza, precyzyjna diagnostyka i zero niespodzianek. Mechanika, "
        "obsługa DPF/EGR, klimatyzacja. Obsługujemy wszystkie marki aut na Al. Grunwaldzkiej 303B."
    )
    
    # Te pola mogą wymagać odpowiednich uprawnień dla Business Page (pages_manage_metadata)
    payload = {
        "about": short_bio,
        "description": long_desc,
        "phone": "+48585522400",
        "emails": ["biuro@rsperformance.pl"],
        "website": "https://rsperformance.online"
    }
    
    post_url = f"https://graph.facebook.com/v19.0/{page_id}"
    headers = {"Authorization": f"Bearer {page_access_token}"}
    
    update_resp = requests.post(post_url, headers=headers, json=payload)
    update_data = update_resp.json()
    
    if "error" in update_data:
        print(" [!] Błąd podczas aktualizacji metadanych profilu (about/desc):")
        print(json.dumps(update_data["error"], indent=2))
    else:
        print(" [v] Sukces! Metadane (Opis, Bio, www) zostały zaktualizowane.")
        if update_data.get("success"):
            print(" -> Facebook potwierdza: success=true")
            
    print("\n3. Próba aktualizacji błędu w nazwie strony (z 'RS Pperformance' na 'RS Performance - Diagnostyka').")
    # Pamiętaj: Często update nazwy strony przez API Graph wymaga manualnego sprawdzenia i akceptacji ze strony Meta.
    name_payload = {
        "name": "RS Performance - Diagnostyka"
    }
    name_resp = requests.post(post_url, headers=headers, json=name_payload)
    name_data = name_resp.json()
    
    if "error" in name_data:
        print(" [INFO] Zmiana nazwy przez API zatrzymana - ze względów bezpieczeństwa FB. Częsty błąd:\n", name_data["error"].get("message", ""))
        print(" Rekomendacja: Zmianę samej NAZWY wyklikaj ręcznie w Meta Business Suite dla przyspieszenia akceptacji.")
    else:
        print(" [v] Wysłano prośbę o zmianę nazwy pomyślnie. Status:", name_data)
        
    print("\n--- SKRYPT ZAKOŃCZONY ---")

if __name__ == "__main__":
    main()
