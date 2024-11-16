<?php
class Transfer {
    public static function new(int $source, int $target, int $amount, mysqli $db) : void {
        // Walidacja kwoty
        if ($amount <= 0) {
            throw new Exception('Kwota przelewu musi być dodatnia');
        }

        // Sprawdzenie, czy nadawca ma wystarczające środki
        $sql = "SELECT amount FROM account WHERE accountNo = ?";
        $query = $db->prepare($sql);
        $query->bind_param('i', $source);
        $query->execute();
        $result = $query->get_result();
        $row = $result->fetch_assoc();

        // Sprawdzanie, czy konto źródłowe istnieje
        if ($row === null) {
            throw new Exception('Konto źródłowe nie istnieje');
        }

        $currentBalance = $row['amount'];
        // Sprawdzenie, czy saldo jest wystarczające
        if ($currentBalance < $amount) {
            throw new Exception('Niewystarczające środki na koncie źródłowym');
        }

        // Rozpoczęcie transakcji
        $db->begin_transaction();
        try {
            // SQL - Odjęcie kwoty z konta źródłowego
            $sql = "UPDATE account SET amount = amount - ? WHERE accountNo = ?";
            $query = $db->prepare($sql);
            $query->bind_param('ii', $amount, $source);
            $query->execute();

            // Dodanie kwoty do konta docelowego
            $sql = "UPDATE account SET amount = amount + ? WHERE accountNo = ?";
            $query = $db->prepare($sql);
            $query->bind_param('ii', $amount, $target);
            $query->execute();

            // Zapisanie informacji o przelewie w bazie danych
            $sql = "INSERT INTO transfer (source, target, amount) VALUES (?, ?, ?)";
            $query = $db->prepare($sql);
            $query->bind_param('iii', $source, $target, $amount);
            $query->execute();

            // Zatwierdzenie transakcji
            $db->commit();
        } catch (mysqli_sql_exception $e) {
            // Jeśli wystąpił błąd, wycofaj transakcję
            $db->rollback();
            // Rzuć wyjątek
            throw new Exception('Przelew nie powiódł się: ' . $e->getMessage());
        }
    }
}
?>