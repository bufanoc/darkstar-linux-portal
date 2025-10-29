FROM ubuntu:24.04

# Install ttyd and basic tools
RUN apt-get update && \
    apt-get install -y \
    ttyd \
    bash \
    coreutils \
    neofetch \
    figlet \
    lolcat \
    curl \
    && apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Create non-root user
RUN useradd -m -s /bin/bash guestuser && \
    echo "guestuser:guestpass" | chpasswd

# Create a welcome script
RUN echo '#!/bin/bash\n\
clear\n\
figlet -f slant "Welcome!" | lolcat\n\
echo -e "\\n\\033[1;35m🌍 Greetings from outer space!\\033[0m\\n"\n\
echo -e "\\033[0;36mYou are connected to an isolated Ubuntu 24.04 container.\\033[0m"\n\
echo -e "\\033[0;36mFeel free to explore (limited commands available).\\033[0m\\n"\n\
neofetch --off\n\
echo -e "\\n\\033[1;33mTry: ls, pwd, whoami, uname -a\\033[0m\\n"\n\
exec /bin/bash' > /usr/local/bin/welcome.sh && \
    chmod +x /usr/local/bin/welcome.sh

# Switch to non-root user
USER guestuser
WORKDIR /home/guestuser

# Start ttyd with the welcome script
CMD ["ttyd", "-p", "7681", "-W", "/usr/local/bin/welcome.sh"]
