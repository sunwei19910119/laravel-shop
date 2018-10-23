<?php

return [
    'alipay' => [
        'app_id'         => '2016092200569703',
        'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwAHi5sR+waCzaE69Ad+8BSUpX57XgoBKtVUI8diG4/uKQXmcwJA8euoKcxxrBDdNHwDe5FgnQDahpjG6ws0hLNNgx7DJYI2pUgmqH18onB/0XHRE0Ec+MbW3Zpbi9pjRqqPspGCCYQwgU5DG0jZpmyouVgYWbLW+cPn77EUH8JrZ22rrivBm5KAXbplXwtx67VKkO5T9D+pSksLpFGLdgvnaRRu0j5woWc1Ax2QE1glrwNZ8SCWzTSJd77t+kxBmuMW8jsz5HpZlUpGzIvpCIwtYcToSlUF3R6ZTTBCrQnpDPQusSPqYL/GuReHjzsV0yzsWRYmyYzS+1piO8FjzHQIDAQAB',
        'private_key'    => 'MIIEpAIBAAKCAQEAn5kE31XNTQ9uCEfeOLwc7SSL/NrYK+GBRX4lPe2EYNmLORwt/eQGz0s7VLZxrAC+pSsN8TVwCSYOlmfCRI89AXaELopa93cknntBSegr2le0ao1TsGkxnnrcjM58CwAKwrXjlSZQ4ZtYNJqNM6O0GfhOf6iY2q3CpHBxtp7dgZSvqqSrB2X7BJPHcXFKcHJAuif/0tkndxfxGvUURkLRRXWa0JD3t4G7vupb9JvNV4Rc1WPIp7HdAQ6ybb29pwJvZfCd2+ptM6giTctuqGfafcfJ7B81e2g74x7ivEQ6suxa52L9KNRdKmUwKRJLAncthF0NzXvAAT1Rcb3iEfIZAwIDAQABAoIBAQCKvC/6HNIsrL8Pv7mzRAM5Ok3ebjcAXjVLvY6OOMycjD90U8S/Mduhk1puF3LU/Ii9d6HVDojnywdfQ68ifgmgHfBlrKvK9u0EP0zP/F0yno8dGOGpNqiFhXP3iv9VaKHyBL9jRj2FAM86WydnvmSvNqS2YE8PuYC8BWs0PwF6w/A47FqOtDOKyA8Pn5Oo+NCaGkLgu+rx0kjZFuvyCV5Q/HIgdUJERBIhFOmhJIzXRPq1HSs4AxaKhsp4phGKsQmkMBbmOjMOhQwBO/MGWW7wLD9oKDv/+yi0EoYU1LhHTdQ/JfDTGXpjLEd95uBNvzVLVB3VMTgm0C8M6elpCDihAoGBAM5mN5OvFCg+lYfT4mY4hkRk28T4EGx9bUdP1t9UIDLEvtPjLnqITkGZXnVKb6+Bkv+hgUnu5pULH4VJ/S7XzmubDcLjKy+cDzhTGMZuC1uUpIZh553N8023R+E2BA03P61NWln4tudA0sSIjmGp7LpQaZtSEMkII6krz16ZsaiLAoGBAMXzjECJmhjgxtopGW6i+Zy24sB7Cj+XYuEs32ZTfM3RmGCc88Eu/iwfrFUk2wjC9nNvwggjvX4FNN4SqkTOM/fn7MpRJif08BHBXB5M33OERzkz7H6u6FBS4jilTosOvYf3y9SMxYhC5IGixPgE5janYpGLgArqXBdZkHqqwuhpAoGABJd9kQmKF0MdLIJoPQHw6sCbqwwhwWgg8D80do92j+qUnD4noI47v3XcBhv71Bm73XgIWk64ToSK1PpKaapfa0Ft0vIe2dZM+GHQ4Uk9c3IhHuQ6tOYKDaaQMNB00p5yCjb4VZwU5ZwplDm9gSq4m4SCdPQkb3SA5piU6nPc4fUCgYAzM+Q7bVxmwv2swKTLcWyPlN7iWvEzsbzp8el3VnEZ6/U2SGLkvXsRfr/c5kOh6IxHH8lYwaXqSv550uooEg5vOZaOXp84BqtybEmfrNK/1p91tHCf9C4zWL0bWecPISj1Wqtmt2MA9AyXW7sHTH0f9fwRGbxtFmHryEcO88ymwQKBgQDD8sRqyk+gSp+IjiR2xNV2xc2tzt4tgYHiST+t3ueJYVfxuqlLdstAMjoJA8yFNs0AdhSqRnRv+oIYHtW6q3HSm4s91Vh+zDRunYsBLM/isxYecYgUe+aVjioEygEP1S+gSXNLpKadfDnIhhSnArqAFWxEdLzisVWJgXWuFuy3kQ==',
        'log'            => [
            'file' => storage_path('logs/alipay.log'),
        ],
    ],

    'wechat' => [
        'app_id'      => '',
        'mch_id'      => '',
        'key'         => '',
        'cert_client' => '',
        'cert_key'    => '',
        'log'         => [
            'file' => storage_path('logs/wechat_pay.log'),
        ],
    ],
];