create type seat_type as enum (
    'hardseat', 'softseat', 'hardcoach_h', 'hardcoach_m', 'hardcoach_l', 'softcoach_h', 'softcoach_l'
);

create type user_type as enum (
    'customer','admin'
);

create type status_type as enum (
    'normal','canceled','expired'
);





create table Train(
    T_ID varchar(5) not null,
    T_NumYZ integer default 5,
    T_NumRZ integer default 5,
    T_NumYW integer default 5,
    T_NumRW integer default 5,
    primary key (T_ID)
);

create table Station (
    S_ID integer,
    S_Name varchar(20),
    S_City varchar(20),
    primary key (S_ID)
);



create table Route (
    R_TrainID varchar(5),
    R_Num smallint,
    R_Station integer,
    R_TimeDep interval,
    R_TimeArr interval,
    R_PriceYZ	decimal(5,1),
    R_PriceRZ	decimal(5,1),
    R_PriceYWS	decimal(5,1),
    R_PriceYWZ	decimal(5,1),
    R_PriceYWX	decimal(5,1),
    R_PriceRWS	decimal(5,1),
    R_PriceRWX	decimal(5,1),
    primary key (R_TrainID,R_Num),
    foreign key (R_Station) references Station(S_ID)
);


create table Userinfo(
    U_ID char(18),
    U_RealName varchar(20) not null,
    U_PhoneNo char(11) unique not null,
    U_CredNo  char(16) not null,
    U_UserName varchar(20) unique not null,
    U_Type user_type default 'customer',
    primary key (U_ID)
);

create table Booking(
    B_ID SERIAL,
    B_UserID   char(18),
    B_TrainID varchar(5),
    B_Date  date not null,
    B_StartSID integer not null, --start station ID
    B_EndSID   integer not null, --end station ID
    B_Price decimal(5,1),
    B_Status status_type,
    primary key (B_ID),
    foreign key (B_TrainID) references Train(T_ID),
    foreign key (B_UserID) references Userinfo(U_ID),
    foreign key (B_StartSID) references Station(S_ID),
    foreign key (B_EndSID) references Station(S_ID)
);

create table Ticket (
    T_TrainID varchar(5),
    T_BookID integer,
    T_RNumDep smallint,
    T_RNumArr smallint,
    T_Date date,
    T_TypeTicket seat_type,
    primary key (T_TrainID,T_BookID),
    foreign key (T_TrainID) references Train(T_ID),
    foreign key (T_BookID) references Booking(B_ID)
);