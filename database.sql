CREATE TABLE author (
    author_id NUMBER PRIMARY KEY,
    name VARCHAR2(100),
    birth_year NUMBER
);

CREATE SEQUENCE author_seq;

CREATE TABLE books_info (
    book_id NUMBER PRIMARY KEY,
    title VARCHAR2(200),
    author_id NUMBER,
    published_year NUMBER,
    total_copies NUMBER,
    available_copies NUMBER
);

CREATE SEQUENCE books_info_seq;

CREATE TABLE members (
    member_id NUMBER PRIMARY KEY,
    name VARCHAR2(100),
    email VARCHAR2(100)
);

CREATE SEQUENCE members_seq;

CREATE TABLE borrow_records (
    borrow_id NUMBER PRIMARY KEY,
    member_id NUMBER,
    book_id NUMBER,
    borrow_date DATE,
    return_date DATE
);

CREATE SEQUENCE borrow_seq;
