---
title: Workshop LAMP (Expert)
author: ing. Sebastiaan Labijn
---

# Inhoudsopgave

1. [Inleiding](#inleiding)
2. [Voorbereiding](#voorbereiding)
3. [Arch Linux](#arch-linux)
4. [SSH en SFTP](#ssh-en-sftp)
6. [MariaDB](#mariadb)
7. [PHP](#php)
8. [Apache](#apache)
7. [Uitbreidingen](#uitbreidingen)
	* [phpMyAdmin](#phpmyadmin)
	* [VirtualBox GuestAdditions](#virtualbox-guestadditions)
	* [Synchronisatie bestanden server via gedeelde map](#synchronisatie-bestanden-server-via-gedeelde-map)

# Inleiding

Tijdens deze workshop gaan we in **VirtualBox** een eenvoudige webserver installeren 
en configureren. Dit gaan we doen door een zogenaamde **LAMP** stack op te zetten. 
LAMP staat voor **Linux – Apache – MariaDB – PHP**. Deze vier technologieën gaan we 
stap voor stap installeren en configureren. Als einddoel hebben we dan een simpele
webserver opgezet in een virtuele omgeving.

Afspraken doorheen deze workshop:
    
* namen van pakketten en belangrijke termen zijn weergegeven in het **vet**
* commando’s zijn weergeven in een kader met voorafgaande command prompt of in-line in **vet**
* acties/selecties/muisklikken worden geplaatst tussen ' '
* toetsenaanslagen worden geplaatst tussen " ", b.v.:&nbsp;"ctrl + c"
* (deel)inhoud van bestanden is in een kader afgedrukt (indien mogelijk met syntaxcoloring)
* enkel de basis installatie wordt als root uitgevoerd. De configuratie nadien 
gebeurt met een gebruiker met sudo rechten

In deze uitgebreidere versie van de workshop wordt er ook meer aandacht besteed 
aan beveiliging van de server door o.a. meerdere gebruikers en partities aan te 
maken.

# Voorbereiding

## Linux distributie downloaden

![Logo Arch Linux](./afb/arch_logo.png)

Als Linux distributie kiezen we voor [**Arch Linux**](https://www.archlinux.org/).
 Deze wordt wegens *bleeding-edge* niet aangeraden voor server installaties maar
 we gebruiken deze omdat we ook de installatiestappen van Linux zelf willen bestuderen.
 Ook wordt deze distributie standaard geïnstalleerd zonder GUI. Download 
[**hier**](https://www.archlinux.org/download/) de laatste versie. Daarnaast heeft deze 
distributie ook een uitgebreide documentatie terug te vinden op 
[**ArchWiki**](https://wiki.archlinux.org/).

## Aanmaken nieuwe virtuele machine

Maak eerst een nieuw host-only network aan. Dit doe je door in **virtualbox** de 
'Host Network Manager' te openen. Kies nadien voor 'create' om een nieuwe adapter aan
 te maken. Stel de waarden in voor deze adapter zoals te zien hieronder.

![Logo Arch Linux](./afb/virtualbox_hostonly.png)

Maak nu in VirtualBox een nieuwe machine aan met volgende parameters:

* Geheugen: minstens 1024 MB (indien meer mogelijk is dit beter)
* Harde Schijf: 20 GB
* 2 Netwerk Adapters 
	* 1: NAT
	* 2: Host-Only (Kies bij 'name'  de aangemaakte netwerkadapter van hierboven)

# Arch Linux

## Voorbereiding

Start de virtuele machine en kies het ISO bestand van ArchLinux om op te starten.
U krijgt volgend scherm te zien

![Live Arch Linux](./afb/arch_live.png)

Aangezien we nog geen besturingssysteem geïnstalleerd hebben kiezen we voor 
'Boot Arch Linux (x86_64)'. Na laden van de installatie komen we in de commandprompt
 terecht en kunnen we starten met de voorbereiding van de installatie.

![Booted Live Arch Linux](./afb/arch_boot.png)

## Toestenbordindeling instellen

Aangezien standaard de US-indeling geladen wordt zal het toetsenbord dus ook in 
**qwerty** formaat staan. Om over te schakelen naar **azerty** laden we de juiste
 toetsenbord combinatie als volgt: 
   
```bash
root@archiso ~ # loadkeys be-latin1
```

## Controleren of we met het internet verbonden zijn

Tijdens en na de installatie zullen we pakketten moeten downloaden. Daarom moeten
 we zeker zijn dat we verbonden zijn met het internet. Om de verbinding te controleren
 voeren we een **ping** uit naar Google:

```bash
root@archiso ~ # ping www.google.be
```

Gebruik "ctrl + c" om het ping commando te stoppen na enkele gelukte pogingen.

Soms laat de netwerkconfiguratie niet toe om een adres te pingen. Het is dan ook 
steeds een goed idee om via **curl** de netwerktoegang te controleren:

```bash
root@archiso ~ # curl icanhazip.com
```

Als uitvoer van dat commando zou je het externe ip van jouw machine moeten zien
 verschijnen. Indien dat niet zo is heb je geen toegang tot het internet.

## Klok goed zetten	

De initiële waarde van de systeemklok is niet altijd accuraat. Om deze bij te
 stellen voeren we volgend commando uit:

```bash
root@archiso ~ # timedatectl set-ntp true
```

## Harde schijf voorbereiden

Aangezien we een nieuw systeem installeren met een nieuwe harde schijf moeten we
 deze eerst partitioneren zodat we straks **Linux** op een partitie kunnen 
installeren. Om een overzicht te krijgen van de aanwezige harde schijven en hun 
partities voeren we volgend commando uit:

```bash
root@archiso ~ # fdisk -l
```

U zou een uitvoer gelijkaardig aan onderstaande schermafbeelding moeten krijgen:

![Uitvoer fdisk -l](./afb/fdisk.png)

In deze workshop gaan we verder uit van **/dev/sda** als primaire harde schijf.
Pas in de commando's dus **sda** aan indien u een andere letter heeft voor jouw 
harde schijf. Om effectief partities op onze schijf aan te maken gaan we met **fdisk** aan de 
slag gaan.

```bash
root@archiso ~ # fdisk /dev/sda
```

Het programma om te partitioneren is nu geladen. Via "m" krijgt u een overzicht 
van alle commando's. Dankzij "p" krijg je een overzicht van de huidige partities 
op de geselecteerde harde schijf (bij ons /dev/sda). De uitvoer zou er als volgt 
moeten uitzien:

![fdisk lijst partities /dev/sda](./afb/fdisk_partities.png)

Controleer zeker of **Disklabel type: dos** is. Indien dat niet zo is voert u 
eerst "o" in als commando om zo een nieuwe partitietabel aan te maken van het type dos.
	
We kiezen er in deze uitgebreide workshop voor om onze schijf in 4 partities op 
te delen.

| Naam partitie | Omschrijving 					 | Bestandstype | Mountpoint |
| :------------ | :----------------------------- | :----------- | :--------- |
| boot			| bevat de bootloader 			 | msdos/fat	| /boot		 |
| root			| bevat de serverprogrammas 	 | ext4			| /			 |
| webserverdata | bevat de data van de webserver | ext4			| /srv/http  |
| swap          |  								 | swap	  	    | -			 |

Er kan eventueel nog gekozen worden om de gebruikersgegevens (**/home**) ook op 
een aparte partitie te plaatsen. In een serveromgeving wordt dit echter bijna nooit
gedaan omdat de grootte van deze map te verwaarlozen is.

Het voordeel van deze manier van werken is dat b.v.: de gegevens de server heel 
gemakkelijk kunnen gemigreerd worden omdat deze op eenzelfde partitie staan.

Om een nieuwe partitie aan te maken voeren we eerst "n" in als commando. 
Nadien drukt u een aantal keer op "enter", controleer steeds of de standaardwaarden
 overeenkomen met de gewenste waarden voor de partities. Maak alle partities aan
volgens de gegevens in onderstaande tabel. De tabel gaat uit van een machine met 1GB
geheugen en 20 GB harde schijf. Indien u meer geheugen genomen heeft, vermindert
u de groote van de tweede partitie (=root) met de extra hoeveelheid geheugen genomen boven 1 GB.
De extra hoeveelheid vermeerder u dan bij de vierder partitie (=swap)

**Voorbeeld:** Indien u 4 GB geheugen nam is de laatste sector van de tweede partitie +9.8G
en de deze van de vierde partitie +4G. In dat geval kloppen ook de waarden van de
eerste sector niet zoals weergegeven in de tabel. U verandert deze dan ook niet en
laat het systeem zelf de waarden invullen.

| Partition type | Partition number | First sector | Last sector |
| :------------: | :--------------: | :----------: | :---------: |
| p              | 1 				| 2048		   | +200MB		 |
| p              | 2 				| 411648	   | +12.8G		 |
| p              | 3 				| 27215872	   | +6G		 |
| p              | 4 				| 39798784	   | +1G	     |
 
Controleer met "p" of de partitietabel goed gemaakt is

![fdisk tabel partities](./afb/fdisk_partitie_tabel.png)

Voer nu het commando "a" uit om de partitie **bootable** te maken.
 De BIOS zal immers zoeken naar een opstartbare harde schijf. Selecteer "1" om de eerste partitie (=bios) bootable te maken.

![fdisk bootable partitie](./afb/fdisk_bootable_expert.png)

Zoals uit onze partitietabel hierboven af te leiden is, klopt het bestandstype van
de eerste en vierde partitie nog niet. Deze moeten **fat** en **swap** worden.

Voer "t" in om het type van een partitie te wijzigen. Kies de gewenste partitie ("1" of "4")
 en geef dan het nummer in van het gewenste bestandstype ("b" voor boot of "82" voor swap).

![fdisk types partities](./afb/fdisk_partitie_types.png)

Controleer nu een laatste maal met "p" de partitietabel.

![fdisk tabel partities](./afb/fdisk_partitie_final.png)

Schrijf nu ten slotte alle wijzigingen naar de harde schijf weg met "w". 
Dit sluit ook **fdisk** af. Hierdoor keren we terug naar onze normale command prompt.
Bij het afsluiten krijgt u een bevestiging te zien dat de partitie effectief werd
aangemaakt.

![fdisk wijzigingen wegschrijven](./afb/fdisk_sync.png)

We hebben nu wel een harde schijf met opstartbare aangemaakt maar deze beschikt 
nog niet over een bestandssysteem per partitie. In deze workshop gaan we 
gebruik maken van het **fourth extended file system** ofwel **ext4**. Dit is de 
standaard bestandsindeling voor huidige Linuxdistributies. Maar voor elke partitie
nu de bestandssystemen aan

```bash
root@archiso ~ # mkfs -t vfat /dev/sda1
root@archiso ~ # mkfs.ext4 /dev/sda2
root@archiso ~ # mkfs.ext4 /dev/sda3
root@archiso ~ # mkswap /dev/sda4
```

## Installeren basis ArchLinux

Nu we een geformatteerde harde schijf hebben moeten we eerst de partities **mounten** 
om de installatie verder te zetten. We kunnen enkel (partities op) een harde schijf  
benaderen indien deze gemount is. Hierdoor verwijst het apparaat naar een bepaalde 
map binnen de bestandsstructuuur van **Linux**. Omdat we enkel kunnen mounten naar
een bestaande map zullen ook bepaalde maeppen eerste aangemaakt moeten worden.

```bash
root@archiso ~ # mount /dev/sda2 /mnt
root@archiso ~ # mkdir /mnt/boot
root@archiso ~ # mount /dev/sda1 /mnt/boot
root@archiso ~ # mkdir -p /mnt/srv/http
root@archiso ~ # mount /dev/sda3 /mnt/srv/http
root@archiso ~ # swapon /dev/sda4
```

Nadat we elke partitie gemount hebben kunnen we de basisonderdelen van **Arch Linux**
 hierop installeren. Deze zijn voor deze workshop uitgebreid met een aantal extra
hulpprogramma's die in **base-devel*** zitten

```bash
root@archiso ~ # pacstrap /mnt base base-devel
```

Tijdens de installatie zullen indien nodig eerst recentere pakketten worden gedownload. 
Dit kan dus even duren. Nadat de installatie voltooid is hebben we een basissysteem 
maar dit dient eerst nog verder geconfigureerd te worden alvorens de machine te 
herstarten!

## Fstab genereren

In het bestand **fstab** (in de map /etc) zit een tabel met een overzicht van 
alle apparaten die deel uitmaken van het filesystem. De inhoud kan u bekijken via

```bash
root@archiso ~ # cat /mnt/etc/fstab
```

Op dit moment is dit bestand nog leeg, we moeten onze harde schijf hier dus nog aan toevoegen. 
Dit doen we door volgend commando uit te voeren

```bash
root@archiso ~ # genfstab -U /mnt >> /mnt/etc/fstab
```

Controleer nu de inhoud van **fstab** of er een entry toegevoegd werd voor elke 
partitie op /dev/sda.

![fstab](./afb/fstab.png)

Om nu de Arch Linux-installatie zelf verder in te stellen moeten als **root** in het 
nieuwe systeem aanmelden, dit doen we als volgt

```bash
root@archiso ~ # arch-chroot /mnt
```

![Arch Chroot](./afb/arch_chroot.png)

Merk op dat hierdoor ook de command prompt aangepast werd!

## Tijdzone instellen

De tijdzone stellen we in door een symbolische link aan te maken tussen de gewenste
 tijdzone en **/etc/localtime**. Het volgende commando stelt de tijdzone in als Brussels

```bash
[root@archiso /]# ln -sf /usr/share/zoneinfo/Europe/Brussels /etc/localtime
```

Synchroniseer daarna de tijd met:

```bash
[root@archiso /]# hwclock --systohc
```

Controleer tenslotte of de datum en tijd correct zijn met het commando **date**

## Taal & Regio instellen

We gaan in deze versie van de workshop werken met **locale** **en_US.UTF-8**. 
Open het bestand **/etc/locale.gen** met **nano** of **vi**. Zoek de regel met 
gewenste locale en verwijder de # aan het begin van de regel. Sla de wijzigingen
 in het bestand op (in vi: duw "esc" en voer dan "wq!" in). Genereer nu de locale.

```bash
[root@archiso /]# locale-gen
```

Om de taal (hier **Engels**") in te stellen voer je volgend commando uit

```bash
[root@archiso /]# echo "LANG=en_US.UTF-8" > /etc/locale.conf
```

Het toetsenbord in de console op **azerty** instellen doen we als volgt:

```bash
[root@archiso /]# echo "KEYMAP=be-latin1" > /etc/vconsole.conf
```

## Initramfs

Nu gaan we de bestanden genereren die toelaten dat linux geboot kan worden, de **initial ramdisk** bestanden.

```bash
[root@archiso /]# mkinitcpio -p linux
```

Tijdens het genereren krijgt u een uitvoer gelijkaardig aan onderstaande afbeelding.
 U zal een waarschuwing krijgen dat firmware **aic94xx** en **wd719x** ontbreken. 
Deze waarschuwingen zullen we wegwerken in [Ontbrekende Firmware](#ontbrekende-firmware)
nadat **SSH** en **SFTP** zijn geïnstalleerd en geconfigureerd.

![Initramfs](./afb/initramfs.png)

## Wachtwoord instellen

Bij een nieuwe installatie moet ook het wachtwoord voor **root** ingesteld worden. 
Zorg er voor dat u dit gemakkelijk kan onthouden!

```bash
[root@archiso /]# passwd
```

## Hostname instellen

Om er voor te zorgen dat ons netwerk IP-adressen op een juiste manier omzet gaan we een **hostname** instellen. Hiervoor moeten we de bestanden **/etc/hostname** en **/etc/hosts** aanpassen. Indien een andere waarde dan **virtualbox** wil dan vervangt u deze waar nodig.

```bash
[root@archiso /]# echo "virtuallamp" > /etc/hostname
```

Open het bestand **/etc/hosts** met **vi** of **nano** en voeg volgende regels toe:

```bash
127.0.0.1	localhost
::1		localhost
127.0.1.1	virtuallamp.localdomain	virtuallamp
```

## Bootloader

Als laatste stap om de installatie af te ronden moeten we ook een bootloader installeren. Deze zorgt voor de verbinding tussen de **BIOS** en de **initramfs**. Zo krijgen we een menu te zien waaruit we kunnen kiezen welk besturingssysteem we starten. Zonder deze bootloader zal de BIOS geen besturingssysteem vinden om te laden! Wij gaan hiervoor gebruik maken van **grub**. 
**os-prober** zorgt er voor dat eventueel andere besturingssystemen zullen gedecteerd worden.
Deze wordt niet standaard mee geïnstalleerd dus dit doen we als volgt:

```bash
[root@archiso /]# pacman -S grub os-prober
```

**Pacman** is de package manager in Arch Linux. Nu is enkel nog maar het pakket **grub** in Arch Linux geïnstalleerd. We moeten er ook voor zorgen dat de code op onze bootbare harde schijf wordt geplaatst om onze initramfs bestanden te vinden. Hiervoor moeten we grub installeren op de bootbare partitie (/dev/sda1) en nadien configureren. Dit doen we als volgt:

```bash
[root@archiso /]# grub-install /dev/sda
[root@archiso /]# grub-mkconfig -o /boot/grub/grub.cfg
```

Indien de installtie gelukt is kan u de chroot omgeving verlaten met **exit**. Nadien gaan we ook onze harde schijf ontmounten en de virtuele machine afsluiten.

```bash
root@archiso ~ # umount -R /mnt
root@archiso ~ # shutdown -h now
```

Verwijder nu het ISO-bestand van de installatie uit de virtuele cd-rom en start de virtuele machine op. Als alles goed gaat komt u op onderstaand menu terecht

![Grub Bootmenu](./afb/grub_boot.png)

Selecteer de eerste optie 'Arch Linux, with Linux core repo kernel' en als alles goed gaat start Arch Linux volledig op. Nadien krijgt u het aanmeldscherm te zien. Hier kan u als root inloggen. Mocht u toch het Arch Linux boot scherm krijgen, dan heeft u het ISO-bestand nog niet verwijdert. Indien dat zo is selecteert u de optie 'Boot existing OS' en dan zou u wel het bovenstaande scherm moeten krijgen.

**Hierdoor is de installatie van Linux geslaagd MAAR hebben we nog geen netwerk …!**

Netwerk instellen

Via het commando **ip link** krijgen we een overzicht van beschikbare netwerk adapters in ons systeem.

![ip link](./afb/ip_link_expert.png)

Onze twee netwerkkaarten zijn down (De namen **enp0s3** en **enp0s8** kunnen verschillen!). Dit komt omdat er nog geen service geactiveerd is die ip's uitdeelt, namelijk **dhcpcd**. We moeten deze service dus eerst activeren en opstarten. Vanaf dan zullen bij elke opstart van het systeem onze netwerkkaarten automatisch een ip ontvangen. 

```bash
[root@virtuallamp ~]# systemctl enable dhcpcd
[root@virtuallamp ~]# systemctl start dhcpcd
```

Algemeen kan u steeds volgende commandos's gebruiken bij een systeemservice. Indien u deze commando's niet als root uitvoerd moet u deze steeds via **sudo** uitvoeren!

```bash
# Service activeren
systemctl enable <naam service>
# Service starten
systemctl start <naam service>
# Status opvrangen van een service
systemctl status <naam service>
# Stoppen van een service
systemctl stop <naam service>
```

Via het commando **ip link** kan u nu controleren of de state UP is voor **enp0s3** en **enp0s8**. U controleert ook best de status van de service **dhcpcd**

![Status dhcpcd](./afb/dhcpcd_status_expert.png)

Herhaal nu ook de commando's, zoals we bij de voorbereiding gedaan hebben, om te controleren of we effectief toegang hebben tot het internet

```bash
[root@virtuallamp ~]# ping www.google.be
[root@virtuallamp ~]# curl icanhazip.com
```

Aangezien we de **host-only** adapter gebruiken om vanuit ons host besturingssysteem de guest te benaderen, zullen we deze een statisch ip geven. Zo kunnen we telkens eenzelfde ip gebruiken in de host browser om de website te testen. Het statische ip, hier **192.168.56.56**, stellen we in op **enp0s8**. Pas in onderstaand commando **enp0s8** indien uw 2de netwerkkaart een andere naam had.

```bash
[root@virtuallamp ~]# ip addr add 192.168.56.56/24 broadcast 192.168.56.255 dev enp0s8
```

Open nu in je host besturingssysteem een terminal/command prompt en voer **ping 192.168.56.56** uit. Indien dit lukt is je guest nog steeds bereikbaar en is de netwerkconfiguratie ook afgerond!

Het nadeel van deze methode is dat de configuratie van het statische ip adres verloren gaat bij afsluiten van de machine. We zouden deze stap dus bij elke boot moeten herhalen, wat heel omslachtig is! 

Om dit op te vangen gaan we een configuratiebestand aanmaken waar de details voor **enp0s8** in opgeslagen zitten zodat het statische ip bij elke boot geladen wordt. Open hiervoor het bestand **/etc/dhcpcd.conf** met **vi** of **nano** en voeg onderaan volgende inhoud toe:

```bash
# Statisch IP voor host-only adapter
interface enp0s8
static ip_address=192.168.56.56/24
static routers=192.168.56.1
```

Sla de wijzigingen op in het bestand. Herstart nu de machine (**reboot**) en voer na inloggen het commando **ip a** uit. Je zou nog altijd 192.168.56.56/24 moeten zien bij enp0s8 en state UP.

![ip a](./afb/ip_a_expert.png)

**TIP:** om na inloggen een overzicht te krijgen van alle ingeladen services gebruik je

```bash
[root@virtuallamp ~]# systemctl --type=service
```

**EXTRA:** om in je shell telkens kleuren te krijgen bij de uitvoer van ls, volstaat het om volgend commando uit te voeren en nadien opnieuw in te loggen. Dit commando zorgt er voor dat er een **alias** aangemaakt wordt en de standaard uitvoer van **ls** aangepast wordt met syntaxcoloring en in jouw bashprofiel wordt geplaatst.
**ELKE** gebruiker moet dit herhalen indien hij ook standaard coloring wil met ls!

```bash
[root@virtuallamp ~]# echo "alias 'ls'='ls --color=always'" >> ~/.bash_profile
```

![alias ls](./afb/alias_ls_expert.png)

Indien je een GUI installeert (komt niet aan bod in deze workshop) en ook in die shell kleuren wilt dan voer je ook onderstaande  command uit.

```bash
[root@virtuallamp ~]# echo "alias 'ls'='ls --color=always'" >> ~/.bashrc
```

## Bijkomende pakketten

Om het werken met de terminal wat te vergemakkelijke installeren we ook de pakketten
**tree** en **vim**. Deze laatste is een verbeterde versie van **vi**.

```bash
[root@virtuallamp ~]# pacman -S tree vim
```

## Standaardgebruiker aanmaken

Na deze paragraaf werken we niet langer meer met de **root** user. Alle commando's
zullen door een **normale** gebruiker uitgevoerd worden in **sudo**. Dit zorgt voor
een betere beveiliging op het systeem omdat een gebruiker dan enkel zaken kan wijzigen
indien dit effectief aangegeven wordt. Voer nu onderstaande commando's uit om de
standaardgebruiker aan te maken, vervang de **virtualbox** door een eigen gebruikersnaam
indien gewenst. Ook deze gebruiker krijgt standaard coloring bij ls.

```bash
[root@virtuallamp ~]# useradd -mG wheel virtualbox
[root@virtuallamp ~]# passwd virtualbox
[root@virtuallamp ~]# echo "alias 'ls'='ls --color=always'" >> /home/virtualbox/.bash_profile
```

Het eerste commando zorgt er voor dat de user **virtualbox** wordt aangemaakt en zijn
homemap **/home/virtualbox** gecreëerd wordt. Ook wordt deze gebruiker aan de groep
**wheel** toegevoegd. Deze groep gaan we gebruiker voor onze **sudo** rechten.
Met het tweede commando stellen we het wachtwoord van de gebruiker in.

De gebruiker zit nu wel in een groep **wheel** die we **sudo** rechtne willen toekennen.
Toch moet deze groep eerst nog ingesteld worden als sudogroep. Open het configuratiebestand
met **visudo** en zoek naar de regel **# %wheel ALL=(ALL) ALL. Verwijder de # aan het begin 
de regel en sla het bestand op. Alle gebruikers uit de groep **wheel** hebben nu
toestemming om commando's met **sudo** uit te voeren. Test dit uit als volgt

```bash
# uitloggen als root
[root@virtuallamp ~]# exit
# Log in met de standaardgebruiker
# Gewone gebruiker kan visudo niet uivoeren
[virtualbox@virtuallamp ~]$ visudo 
# Als sudo lukt dit wel
[virtualbox@virtuallamp ~]$ sudo visudo
# Sluit visudo af zonder wijzigingen (met :q)
```

**Aandacht:** het feit dat er een $ op het einde van de commandprompt staat duidt
op het feit dat u normale rechten heeft. Een # toont aan dat u verhoogde rechten heeft.

Hiermee is de installatie van Arch Linux volledig en kunnen we beginnen met het toevoegen van onze server functionatiteiten.

**Nogmaals, vanaf nu wordt nergens nog rechtstreeks met de user root gewerkt!**

# SSH en SFTP

**SSH** (Secure SHell) laat ons toe om vanuit de host in te loggen op de guest en
zo commando's uit te voeren. Het voordeel hiervan is dat we niet meer rechtstreeks
op de virtuele machine zullen werken. Met andere woorden, u kan vanop afstand
een verbinding maken met de machine. Deze kan daarna gerust op een andere (fysieke) locatie
zich bevinden.

## Installatie 

**SSH** is een service (deamon) en zal dus moeten toegevoegd en nadien geactiveerd worden.
Wij kiezen voor **openssh** als pakket om deze service te verzorgen.

Log eerst, indien nodig, in als **standaardgebruiker**

```bash
[virtualbox@virtuallamp ~]$ sudo pacman -S openssh
[virtualbox@virtuallamp ~]$ sudo systemctl enable sshd
[virtualbox@virtuallamp ~]$ sudo systemctl start sshd
[virtualbox@virtuallamp ~]$ sudo systemctl status sshd
```

## Configuratie

We hebben nu wel **SSH** geactiveerd maar nog niet ingesteld dat onze standaardgebruiker
deze service mag gebruiken. Open hiervoor het bestand **/etc/ssh/sshd_config** met **vim** of **nano**
als **sudo**. Zoek naar de regel **#Port 22**. Verwijder de # aan de start van de regel.
het is ook aan te raden om het poortnummer in te stellen op een ander nummer. Dit op 22 laten 
staan zorgt voor een groter beveiligingsrisico omdat dit de standaard poort is van deze service.
het is echter aan te raden een getal groter dan 1024 te nemen, b.v.: **22222**.
Ga vervolgens op zoek naar de regel **#Banner none** en wijzig deze naar **Banner /etc/issue**.
Als laatste wijziging voeg je onderaan in het bestand volgende regel toe:
**AllowUsers virtualbox**. Deze zorgt er voor dat onze standaardgebruiker toegang
krijgt om via SSH te verbinden met de virtuele machine. Bewaar nu de wijzigingen in het bestand
en herstart de service **sshd**. Controleer de status!

Nu **SSH** correct is ingesteld gaan we proberen een verbinding te maken vanuit de host. 

Indien uw hostsysteem **Windows** is kan u gebruik maken van een toepassing **putty**. 
Dit kan u [**hier**](https://www.putty.org/) downloaden. Open de toepassing en vul **192.168.56.56** in bij host
en **22222** bij port. Duw dan op 'ok' en als alles goed gaat krijgt u nu de vraag
om uw gebruikersnaam in te geven en wachtwoord. Nadien bent u als standaardgebruiker ingelogd
en krijgt u bijhordende commandt prompt te zien.

## TODO screenshot van putty uit windows!!!

Als u **Linux** of **MacOs** als host gebruikt kan u proberen een connectie te 
maken vanuit de terminal met het commando **ssh virtualbox@192.168.56.56 -p 22222**

Accepteer de boodschap i.v.m. keys. Na invoeren van het wachtwoord komt u in de command
prompt van de server terecht.

![SSH Linux/MacOs](./afb/ssh_linux.png)

U kan vanaf nu kiezen: ofwel logt u in op de virtuele machine en voert u de commando's
daar in de terminal uit. U kan ook opteren om vanuit de host via **SSH** te verbinden
en via deze command prompt te werken. In de kaders met instructie in deze handleiding
zal u **geen** verschil merken omdat de command prompt er in beide gevallen hetzelfde
zal uitzien!

## SFTP

**SFTP** (Secure File Transfer Protocol) zal er voor zorgen dat we vanuit de host
met een FTP-client bestanden kunnen opladen naar de server. Dit is vooral handig
om (configuratie)bestanden op een eenvoudige manier op de server te plaatsen
zonder deze manueel te moeten typen en aanpasssen op de server zelf. Door **openssh**
te installeren en configuren kunnen we ook gebruik maken van **SFTP**. We moeten 
dus geen aparte **FTP** server opzetten zoals in de basis workshop wel het geval was!

### Verbinden

Om via **SFTP** te verbinden vanuit de host moeten we gebruik maken van een FTP-client.
Wij kiezen voor [**FileZilla**](http://filezilla-project.org). Open het programma na installatie 
en ga naar 'File' -> 'Site Manager ...' of via "Ctrl + S". Voeg een 'New Site' toe en vul de gegevens aan.

![FileZilla new site](./afb/filezilla_site.png)

Via 'Connect' zal er dan een verbinding gemaakt worden. Accepteer ook hier eerst
de ongekende sleutel. Indien de verbinding succesvol tot stand is gebracht komen
 we in de **home** map uit van onze standaardgebruiker.

![FileZilla connectie](./afb/filezilla_connect.png)

# MariaDB 

Aangezien onze webserver een **PHP** applicatie zal draaien die een databank (**MySQL**) gebruikt zullen we eerst de databank installeren en configureren, nadien volgt PHP.

## Installatie

Om **MariaDB** te installeren loggen we, indien nodig, eerst in onze distribute in als **standaardgebruiker**. Na inloggen voeren we de installatie uit met onderstaande commando.

```bash
[virtualbox@virtuallamp ~]# sudo pacman -S mariadb
```

Nu MariaDB geïnstalleerd is moeten we ook de mappen aanmaken waarin onze databank zijn data zal opslaan. 

```bash
[virtualbox@virtuallamp ~]# sudo mysql_install_db --user=mysql --basedir=/usr --datadir=/var/lib/mysql
```

Net zoals bij een nieuwe Linux installatie het geval was, is ook bij MySQL het wachtwoord na installatie leeg. Dit moet dus eerst ingesteld worden maar dit is enkel mogelijk als **MariaDB** zelf draait. We hebben op dit moment enkel de installatie gedaan maar net zoals bij **dhcpcd** het geval was draait de service nog niet.

```bash
[virtualbox@virtuallamp ~]# sudo systemctl enable mariadb
[virtualbox@virtuallamp ~]# sudo systemctl start mariadb
```

Controleer of het starten effectief gelukt is èn de service MariaDB correct draait. 

```bash
[virtualbox@virtuallamp ~]# sudo systemctl status mariadb
```

![Status MariaDB](./afb/mariadb_status.png)

## Configuratie

Nu onze service draait kunnen we dus ook het wachtwoord voor de mysql rootgebruiker aanpassen. Voer hiervoor **mysql_secure_installtion** uit. Het huidige wachtwoord is leeg dus duw bij de eerste vraag op "enter". Antwoord nadien op elke vraag met "Y" en voer indien gevraagd het gewenste nieuwe wachtwoord in.

## Testdatabank aanmaken

Nu de configuratie van MariaDB zelf voltooid is kunnen we onze databank aanmaken die we zullen gebruiken voor onze webapplicatie. Log hiervoor eerst in bij mysql met de user root met het commando **mysql -u root -p**
Voer bij het wachtwoord dit van mysql in, het wachtwoord dat je in vorige paragraaf hebt ingesteld, EN NIET jouw eigen root wachtwoord! 

Indien je succesvol aangemeld bent zal de prompt er uitzien als **MariaDB [(none)]**. Voer ondertaande commando's één voor één uit om de testdatabank aan te maken
Het voordeel van nu verbonden te zijn via **SSH** is dat je tekst kan plakken in de command prompt. Zo hoeft u niet alles over te typen
maar kan u via 'copy' en 'paste' (= rechter muis knop) de tekst overbrengen.

```sql
MariaDB [(none)] create database test;
MariaDB [(none)] use test;
MariaDB [(test)] CREATE TABLE user (name VARCHAR(50) NOT NULL, PRIMARY KEY (name));
MariaDB [(test)] insert into user values ('Jan');
MariaDB [(test)] insert into user values ('Bert');
MariaDB [(test)] insert into user values ('Pieter');
MariaDB [(test)] insert into user values ('Tom');
MariaDB [(test)] insert into user values ('Stijn');
MariaDB [(test)] select * from user order by name;
```

Als u na het laatste commando onderstaande uitvoer krijgt is de testdatabank klaar:

![Select MariaDB](./afb/mariadb_select.png)

Verlaat mariadb via **exit** en u keert terug naar de prompt van de standaardgebruiker.

Hiermee is de installatie en configuratie voor MariaDB klaar. 

**AANDACHT:** in een professionele omgeving worden aparte gebruikers aangemaakt per databank. Hier wordt voor de gemakkelijkheid enkel de root user gebruikt.

**EXTRA:** om niet altijd alle SQL queries via command prompt te moeten ingeven kan je ook het pakket **phpMyAdmin** installeren om via de browser op de host jouw databank te beheren (zie [Uitbreidingen](#uitbreidingen)).

# PHP

De volgende stap is om **PHP** te installeren. PHP is een eenvoudig aan te leren scripttaal waarbij we serverside inhoud kunnen genereren om zo onze HTML pagina's verrijken.

## Installatie

Om PHP te installeren loggen we, indien nodig, eerst in onze distribute in als root. Nadien installeren we PHP als volgt:    

```bash
[root@virtualbox ~]# pacman -S php
```

## Configuratie

Nu PHP geïnstalleerd is moeten we ook een aantal zaken gaan configureren. PHP is geen service zoals **MariaDB** of **Apache** en moet dus ook niet geactiveerd worden. Standaard zijn echter heel wat uitbreidingen niet geactiveerd. Aangezien wij via PHP onze MySQL databank wensen te bevragen zullen wij deze functionaliteit moeten activeren. Dit doen door het bestand **/etc/php/php.ini aan te passen**. Open het bestand met **vi** of **nano** en zoek naar de regel **;extension=pdo_mysql** en verwijder de ; aan het begin van de regel om deze extensie te activeren. Sla de wijzigingen in het bestand op. Controleer nu of het bestand **pdo_mysql.so** aanwezig is in de map **/usr/lib/php/modules/**

**TIP:** Het kan ook handig zijn om **display errors = on** in **/etc/php/php.ini** te plaatsen. Deze staat nu nog op 'off'. Indien deze 'on' staat zullen eventuele fouten in de PHP pagina getoond worden in de browser. Indien deze waarde op off staat zal er bij fouten enkel een witte pagina getoond worden. De waarde 'on' is dus enkel nuttig voor ontwikkelomgevingen. In productie staat deze waarde op 'off'

**AANDACHT:** Elke wijziging in php.ini zorgt er voor dat de Apache server moet herstart worden (zie [Apache](#apache)).

Hiermee zit de installatie en configuratie voor PHP er op.

# Apache

Als laatste stap om onze **LAMP** stack te vervolledigen gaan **Apache** installeren. Hierop zullen wij dan een webapplicatie draaien bestaande uit twee eenvoudige PHP pagina's om aan te tonen dat PHP effectief draait en we data uit onze testdatabank kunnen ophalen.

## Installatie

Om Apache te installeren loggen we, indien nodig, eerst in onze distribute in als root. We installeren naast **Apache** ook al onmiddellijk de uitbreiding voor PHP mee. Na inloggen voeren we in de commandprompt het volgende uit.

```bash
[root@virtualbox ~]# pacman -S apache php-apache
```

Net zoals bij MariaDB het geval was, moeten we ook Apache als service activeren en starten. Merk op dat de naam van de service niet **Apache** is maar **httpd**!

```bash
[root@virtualbox ~]# systemctl enable httpd
[root@virtualbox ~]# systemctl start httpd
```

Controleer ook nu de status van de **httpd** service

```bash
[root@virtualbox ~]# systemctl status httpd
```

Onze Apache webserver draait nu. Dit betekent dat we via een browser in ons host besturingssysteem naar http://192.168.56.56 kunnen surfen en zo de indexpagina van onze wesbite bereiken. Test dit nu uit!

![Indexpagina Apache](./afb/apache_index.png)

## Configuratie

Nu onze service draait gaan we deze verder configureren zodat we PHP pagina's kunnen laden. Het eerste wat we moeten instellen is de locatie waar de bestanden van onze website zullen komen. Dit doen we door het bestand **/etc/httpd/conf/httpd.conf** aan te passen, dus open dit bestand via **vi** of **nano**. Zoek naar volgende regels:

```bash
LoadModule mpm_event_module modules/mod_mpm_event.so
#LoadModule mpm_prefork_module modules/mod_mpm_prefork.so
``` 

Verplaats nu de **#** van de regel met prefork naar de regel met event. We willen immers de **mpm_prefork_module** gebruiken omdat deze beter overweg kan met threading bij PHP.

Voer onder de laatste #loadmodule regel volgende regels specifiek voor php toe:

```bash
LoadModule php7_module modules/libphp7.so
AddHandler php7-script .php
```

Zoek nu verder naar de regels:

```bash
DocumentRoot "/srv/http"
<Directory "/srv/http">
```

Deze instelling geeft aan dat wij de documenten van onze website in de map **/srv/http/** zullen plaatsen. Voor de eenvoud van deze workshop worden deze mappen NIET aangepast.

Zoek nu naar de regels:

```bash
<IfModule dir_module>
	DirectoryIndex index.html
</IfModule
```

Voer na index.html ook de tekst index.php toe.

Ga nu helemaal naar onder in het bestand en voeg volgende regels toe specifiek voor **PHP 7**:

```bash
# PHP 7
Include conf/extra/php7_module.conf
```

Sla de wijzigen op in het bestand en herstart nu de httpd service met **systemctl restart httpd**

Controleer steeds na het herstarten van een service zijn status!

## Testpagina's toevoegen

Als laatste stap gaan we nu twee pagina's toevoegen op onze website. Zoals uit vorige paragraaf bleek moeten we deze bestanden aan de **/srv/http** map toevoegen. Voer volgend command uit om de pagina **index.php** aan te maken waarin we de detaisl van onze PHP installatie weergeven.

```bash
[root@virtualbox ~]# echo "<?php phpinfo(); ?>" > /srv/http/index.php
```

Open nu op de host jouw browser opnieuw en surf naar http://192.168.56.56/ Indien alles gelukt is ziet u de informatie pagina van PHP zoals hieronder

![PHP overzichtpagina Apache](./afb/apache_php.png)

Indien dit zo is dan is de integratie van PHP met Apache gelukt. Een laatste stap is nu een pagina te maken waarbij onze data uit de databank geladen word. Maakt hiervoor in de map **/srv/http** een bestand **databank.php** aan en plaats volgende code in dat bestand.

```php
<?php
// De details voor connectie met databank
$DB_host = "localhost";
$DB_port = "3306";
$DB_user = "root";
$DB_password = "VUL DIT AAN MET JOUW WACHTWOORD";
$DB_name = "test";

// Proberen om een databank connectie op te zetten
try
{
    $DB_con = new PDO("mysql:host=$DB_host:$DB_port;dbname=$DB_name",$DB_user,$DB_password);
    $DB_con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e)
{
    echo $e->getMessage();
}

try {
    // alle testusers ophalen 
    $sql = "SELECT * FROM user ORDER BY name";
    $stmt = $DB_con->prepare($sql); 
    $stmt->execute();
    // De resultaten van de query afdrukken in onze pagina
    $gebruikers = $stmt->fetchAll();
    print_r($gebruikers);
} 
catch (PDOException $e) {
    echo $e->getMessage();
}

?>
```

Ga nu in de browser van je host naar http://192.168.56.56/databank.php en u zou een overzicht van de gebruikers moeten zien als volgt:

![Databank Apache](./afb/apache_databank.png)

Dit betekent dat we onze MySQL databank kunnen aanspreken vanuit PHP op onze Apache webserver. Hiermee is onze LAMP stack opgezet!!

**EXTRA:** om niet altijd bestanden manueel te moeten typen op onze server kiezen we uiteraard voor een gemakkelijkere manier. Enerzijds kan je een **FTP** server (of **SFTP**) opzetten zodat je met een FTP-client vanop de host bestanden kan opladen naar de map van de webserver. Anderzijds kan je ook een script uitvoeren die automatisch alle gewijzigde bestanden uit een gedeelde map op de host kopieert naar de map van de website op de server. Beide oplossingen zijn beschreven in [Uitbreidingen](#uitbreidingen).

# Uitbreidingen

## phpMyAdmin

Om het beheer van de databank te vergemakkelijken en dus zo het gebruik van **MySQL** prompt zoveel mogelijk te vermijden zullen we gebruik maken van het pakket **phpMyAdmin**. Dit zal ons in staat stellen om via een browser op de host omgeving de databank aan te spreken in de virtualbox en deze te beheren.

### Installatie

Indien nodig log je als root in de virtuele machine in. Voer de installatie uit met het commando

```bash
[root@virtualbox ~]# pacman -S phpmyadmin
```

Aangezien deze uitbreiding, zoals de naam al aangeeft, gebruik zal maken van PHP moeten we ook de extensie activeren om met **mysqli** te werken. Open het bestand **/etc/php/php.ini** in **vi** of **nano** en zoek naar de regel **;extension=mysli** en verwijder de ; op het begin van deze regel.

### Configuratie

Nu de extensie ingeschakeld is moeten we ook in **Apache** een directory toevoegen die verwijst naar de **phpMyAdmin** bestanden. Om dit te doen openen we het bestand **/etc/httpd/conf/httpd.conf** in **vi** of **nano**. Zoek naar de **Include** regel van PHP7 (zie [PHP](#php)) en voeg daaronder volgende regels toe:

```bash
# phpMyAdmin
Include conf/extra/phpmyadmin.conf
```

Aangezien dit bestand **/etc/httpd/conf/extra/phpmyadmin.conf** nog niet bestaat gaan we dit nu aanmaken. Open nadien het bestand met **vi** of **nano** en voeg volgende inhoud toe:

```bash
Alias /phpmyadmin "/usr/share/webapps/phpMyAdmin"
<Directory "/usr/share/webapps/phpMyAdmin">
    DirectoryIndex index.php
    AllowOverride All
    Options FollowSymlinks
    Require all granted
</Directory>
```

Dit zal er voorzorgen dat we via http://192.168.56.56/phpmyadmin de applicatie kunnen bereiken dankzij de alias die we gedefinieerd hebben. Alvorens dit zo is moeten we uiteraard eerst onze **httpd** service herstarten

```bash
[root@virtualbox ~]# systemctl restart httpd
[root@virtualbox ~]# systemctl status httpd
```

Bij syntax fouten in het configuratiebestand zal de service niet goed gestart zijn! Indien het lukt om te pagina te laden krijgt u onderstaand scherm te zien:

![Aanmelden phpMyAdmin](./afb/phpmyadmin_login.png)

Probeer op deze startpagina als root in te loggen met uw **mysql wachtwoord**. Na aanmelden krijgt u volgend dashboard te zien
 
![Dashboard phpmyadmin](./afb/phpmyadmin_dashboard.png)

Hierdoor is de installatie van **phpMyAdmin** geslaagd. Links in de boomstructuur kan u de aangemaakte database test terugvinden. Als u deze openklapt kan u de tabel user terugvinden en eventueel aanpassen.

## FTP server

Momenteel maken we de bestanden onze server manueel aan en wordt ook op de server zelf de inhoud via **vi** of **nano** toegevoegd. Dit is echter zeer omslachting. Om bestanden van uit onze host te kunnen opladen naar onze server gaan we een **FTP** service opzetten met **bftpd** (Voor **SFTP** en **SSH** zie **Workshop LAMP expert**](/workshop_lamp_expert.md)).

### Installatie

Indien nodig log je als root in de virtualbox in. Voer de installatie uit met het commando

```bash
[root@virtualbox ~]# pacman -S bftpd
```

### Configuratie

De configuratie van **bftpd** verloopt via het bestand **/etc/bftpd.conf**. Open dit bestand en ga helemaal naar onder. Pas daar de tekst voor user root aan als volgt:

```bash
user root {
	ROOTDIR="/srv/http"
}
```

Aangezien **FTP** een service is moeten we deze dus opnieuw activeren en starten:

```bash
[root@virtualbox ~]# systemctl enable bftpd
[root@virtualbox ~]# systemctl start bftpd
```

Gebruikt nu een ftp-client op de host, b.v.: **FileZilla**, en maak een verbinding als root user. U kan ook in de browser surfen naar [**ftp://192.168.56.56**](ftp://192.168.56.56/) en aanmelden als root. In beide gevallen zal u de hoofdmap van onze website zien met daarin de eerder aangemaakte PHP bestanden:
 
![Index ftp server](./afb/ftp_index.png)

Indien u iets anders wil dan een bestand downloaden, b.v.: naam wijzigen, bestand opladen, dan is dat enkel mogelijk door gebruik te maken van een FTP-client.

## VirtualBox GuestAdditions

Het installeren van de **GuestAdditions** zal ons toelaten een paar extra zaken te gebruiken zoals onder andere gedeelde mappen en een gedeeld klembord. Dit kan handig zijn om tekst vanuit een host te kunnen plakken in de guest.

### Installatie

Indien nodig, log in als root. Voer daarna de installatie uit.

```bash
[root@virtualbox ~]# pacman -S virtualbox-guest-modules-arch
[root@virtualbox ~]# pacman -S virtualbox-guest-utils
[root@virtualbox ~]# systemctl enable vboxservice
[root@virtualbox ~]# systemctl start vboxservice
```

### Gedeelde map

Indien u een gedeelde map wil gebruiken moet u nu eerst de virtuele machine afsluiten (**shutdown -h now**). Open de instellingen van de server in VirtualBox en ga naar 'shared folders'. Voeg hier een nieuwe map toe die je wil delen. Zorg er zeker voor dat de optie auto-mount aangevinkt werd en dat de naam **GEEN** spaties bevat. Start nu de machine opnieuw op en log in als root. In de map **/media** zou nu een map moeten zien met als naam **sf_<naam gedeelde map>**

## Synchronisatie bestanden server via gedeelde map

Om het manuele werk dat we moeten doen via **FTP** door telkens de bestanden op te laden te vergemakkelijken zullen we bestanden uit een bepaalde map op de host automatisch synchroniseren naar de website map op de server. 

**LET OP:** als u deze methode toepast wordt het effect van de FTP server teniet gedaan, want alle rechtstreeks wijzigingen in **/srv/http** zullen ongedaan gemaakt worden door de synchronisatie!

**AANDACHT:** dit deel gaat er vanuit dat u de **VirtualBox GuestAdditions** al heeft geïnstalleerd (zie [VirtualBox GuestAdditions](#virtualbox-guestadditions)) en een gedeelde map heeft aangemaakt.

### Configuratie

Het script dat automatisch zal synchroniseren zal draaien als een systeemservice. We gaan dus een eigen service schrijven en deze toevoegen. 

De synchronisatie zelf gebeurd via **rsync**. Dit pakket is niet standaard meegeleverd dus dit zullen we eerst installeren.

```bash
[root@virtualbox ~]# pacman -S rsync
```

Eerst maken we het script aan met de code tot synchronisate. Maak een bestand **sync.sh** aan in de map **/root** en plaats volgende code in het bestand:

```bash
#!/bin/bash
# De mappen (sf = shared folder, wf = website folder)
sf="/media/<PLAATS HIER DE NAAM VAN JOUW MAP>/"
wf="/srv/http"
# Synchronisatie uitvoeren (verborgen bestanden niet mee syncen)
rsync -az --quiet --no-perms --delete --exclude ".*" "$sf" "$wf"
```

Zorg er voor dat dit bestand **uitvoerbaar** is voor **root** en **group** via **chmod 770 sync.sh**. Voer nu het commando **cp /srv/http/&ast; /media/sf_<naam gedeelde map>** uit. Dit zorgt er voor dat de bestanden van de website eerst naar de gedeelde map worden gekopieerd. Anders was je deze kwijt door synchronisatie daar ze nog niet op de host aanwezig zijn. Je kan dit script nu testen op zijn werking door **./sync.sh** uit te voeren. Plaats een leeg bestand in de gedeelde map op de host en controleer in de guest of het bestand werd overgezet. Dit kan bijvoorbeeld door het **tree** commando uit te voeren. Dit is een heel handig commando dat een boomstructuur van een map toont. Dit is opnieuw niet standaard geïnstalleerd
	
```bash
[root@virtualbox ~]# pacman -S tree
[root@virtualbox ~]# tree /srv/http
```

![tree](./afb/tree.png)

Het script werkt maar het nadeel is nu dat de synchronisatie nog altijd manueel moet geactiveerd worden. Om dit op te lossen gaan we nu een systeemservice aanmaken dit het script voor ons elke 5 seconden zal oproepen. Maak een bestand **websitesync.service** aan in de map **/usr/lib/systemd/system** met volgende inhoud:

```bash
[Unit]
Description=Sync Website

[Service]
ExecStart=/root/sync.sh
Restart=always
# Synchronisatie elke 5 seconden uitvoeren
RestartSec=5

[Install]
Alias=websitesync.service
WantedBy=multi-user.target
```

Sla het bestand op en controleer of het system de service kan laden met

```bash
[root@virtualbox ~]# systemctl list-unit-files | grep website
```

![Systeemservice websitesync](./afb/websitesync.png)

Het enige wat nu nog rest is de service effectief te activeren en te starten volgens

```bash
[root@virtualbox ~]# systemctl enable websitesync.service
[root@virtualbox ~]# systemctl start websitesync.service
```

Plaats nu nog enkele bestanden op de host in de gedeelde map en controleer of deze ook op de server er bij komen.
Herstart ook de virtuele machine en controleer de status van de service na reboot. Deze zou nog altijd moeten actief zijn.

Om de status van een service constant, in ons voorbeeld om de halve seconde, te monitoren kan u gebruik maken van onderstaand commando.

```bash
[root@virtualbox ~]# watch –n 0.5 systemctl status websitesync.service
```

Deze 'blokkeert' wel de terminal voor gebruikersinvoer, dus u kan de **watch** altijd onderbreken met "ctrl + c".

**TIP:** voeg het commando van **watch** als alias toe aan .bash_rc om zo gemakkelijk nadien de synchronisatie te kunnen monitoren zonder altijd he volledige commando te moeten typen

```bash
[root@virtualbox ~]# echo "alias 'watchsync'='watch –n 0.5 systemctl status websitesync.service'" >> ~/.bashrc
```
