FROM ubuntu:24.04

# Install ttyd, frotz (Z-machine interpreter), and basic tools
RUN apt-get update && \
    apt-get install -y \
    ttyd \
    frotz \
    bash \
    coreutils \
    figlet \
    lolcat \
    curl \
    wget \
    && apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Create non-root user
RUN useradd -m -s /bin/bash guestuser && \
    echo "guestuser:guestpass" | chpasswd

# Download ZORK I data file (freely available from Infocom via archive.org)
RUN mkdir -p /usr/local/share/zork && \
    cd /usr/local/share/zork && \
    wget -q https://github.com/historicalsource/zork1/raw/master/DATA/ZORK1.DAT -O zork1.dat || \
    wget -q http://www.ifarchive.org/if-archive/games/zcode/zdungeon.z5 -O zork1.dat && \
    chmod 644 zork1.dat

# Create ZORK launch script with welcome message
RUN echo '#!/bin/bash\n\
clear\n\
echo -e "\\033[1;35m"\n\
figlet -f slant "ZORK I"\n\
echo -e "\\033[0m"\n\
echo -e "\\033[1;36m═══════════════════════════════════════════════════════════\\033[0m"\n\
echo -e "\\033[1;33m        THE GREAT UNDERGROUND EMPIRE\\033[0m"\n\
echo -e "\\033[0;36m         A Text Adventure Classic\\033[0m"\n\
echo -e "\\033[1;35m           From Outer Space!\\033[0m"\n\
echo -e "\\033[1;36m═══════════════════════════════════════════════════════════\\033[0m"\n\
echo -e "\\033[0;37m"\n\
echo "Welcome, brave adventurer! You are about to enter the"\n\
echo "legendary world of ZORK, transmitted from deep space..."\n\
echo ""\n\
echo -e "\\033[1;32mCommands: LOOK, GO [direction], TAKE [item], INVENTORY\\033[0m"\n\
echo -e "\\033[1;33mType QUIT to exit the game\\033[0m"\n\
echo -e "\\033[0;37m"\n\
sleep 2\n\
exec /usr/games/frotz -p /usr/local/share/zork/zork1.dat' > /usr/local/bin/zork.sh && \
    chmod +x /usr/local/bin/zork.sh

# Switch to non-root user
USER guestuser
WORKDIR /home/guestuser

# Start ttyd with ZORK
CMD ["ttyd", "-p", "7681", "-W", "/usr/local/bin/zork.sh"]
