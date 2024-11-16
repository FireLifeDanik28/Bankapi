<?php
class Transfer {
    public static function new(int $source, int $target, int $amount, mysqli $db) : void {
        $db->begin_transaction();
        if($amount <= 0){
            //jeżeli wartość jest nie pozytywna to wycofaj transakcje
            $db->rollback();
            //rzuć wyjątek
            throw new Exception('Liczba wynosi mniej niż 0');
        } else
        try {
            // sprawdź wartość wysłąną
            $sql = "SELECT amount FROM account WHERE accountNo = ?";
            $query = $db->prepare($sql);
            // podmień numer konta źródłowego na zapytanie
            $query->bind_param('i', $source);
            // uzyskaj aktualny stan konta
            $query->execute();
            // pobierz wynik zapytania
            $result = $query->get_result();
            // Pobierz tablicę
            $row = $result->fetch_assoc();

            // sprawdź, czy sender nie jest biedakiem
            if ($row['amount'] < $amount) {
                // wycofaj transakcje
                $db->rollback();
                // rzuć wyjątek
                throw new Exception('Nie masz kasy biedaku');
            }

            //sql - odjęcie kwoty z rachunku 1
            $sql = "UPDATE account SET amount = amount - ? WHERE accountNo = ?";
            //przygotuj zapytanie
            $query = $db->prepare($sql);
            //podmień znaki zapytania na zmienne
            $query->bind_param('ii', $amount, $source);
            //wykonaj zapytanie
            $query->execute();
            //dodaj kwotę do rachunku 2
            $sql = "UPDATE account SET amount = amount + ? WHERE accountNo = ?";
            //przygotuj zapytanie
            $query = $db->prepare($sql);
            //podmień znaki zapytania na zmienne
            $query->bind_param('ii', $amount, $target);
            //wykonaj zapytanie
            $query->execute();
            //zapisz informację o przelewie do bazy danych
            $sql = "INSERT INTO transfer (source, target, amount) VALUES (?, ?, ?)";
            //przygotuj zapytanie
            $query = $db->prepare($sql);
            //podmień znaki zapytania na zmienne
            $query->bind_param('iii', $source, $target, $amount);
            //wykonaj zapytanie
            $query->execute();
            //zakończ transakcje
            $db->commit();
        } catch (mysqli_sql_exception $e) {
            //jeżeli wystąpił błąd to wycofaj transakcje
            $db->rollback();
            //rzuć wyjątek
            throw new Exception('Transfer failed');
        }

    }
}
?>