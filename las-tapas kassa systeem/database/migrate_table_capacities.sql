USE las_tapas;

UPDATE tafels
SET capaciteit = CASE
    WHEN naam IN ('Tafel 1', 'Tafel 2', 'Tafel 3') THEN 2
    WHEN naam IN ('Tafel 4', 'Tafel 5', 'Tafel 6', 'Tafel 7') THEN 4
    WHEN naam IN ('Tafel 8', 'Tafel 9', 'Tafel 10') THEN 6
    WHEN naam IN (
        'Tafel 11', 'Tafel 12', 'Tafel 13', 'Tafel 14',
        'Tafel 15', 'Tafel 16', 'Tafel 17', 'Tafel 18',
        'Tafel 19', 'Tafel 20', 'Tafel 21', 'Tafel 22',
        'Tafel 23', 'Tafel 24', 'Tafel 25', 'Tafel 26'
    ) THEN 8
    ELSE capaciteit
END
WHERE naam IN (
    'Tafel 1', 'Tafel 2', 'Tafel 3', 'Tafel 4', 'Tafel 5',
    'Tafel 6', 'Tafel 7', 'Tafel 8', 'Tafel 9', 'Tafel 10',
    'Tafel 11', 'Tafel 12', 'Tafel 13', 'Tafel 14', 'Tafel 15',
    'Tafel 16', 'Tafel 17', 'Tafel 18', 'Tafel 19', 'Tafel 20',
    'Tafel 21', 'Tafel 22', 'Tafel 23', 'Tafel 24', 'Tafel 25',
    'Tafel 26'
);
