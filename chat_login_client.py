import socket


HOST = "10.122.24.34"
PORT = 5002


def recv_line(sock, buf=b""):
    """
    Nhận dữ liệu và tách thông điệp theo \n.
    """

    while b"\n" not in buf:
        chunk = sock.recv(1024)

        if not chunk:
            return None, buf

        buf += chunk

    line, buf = buf.split(b"\n", 1)

    return line.decode("utf-8"), buf


def main():
    sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)

    try:
        sock.connect((HOST, PORT))

        print("=" * 50)
        print("CHAT LOGIN CLIENT")
        print(f"Kết nối server {HOST}:{PORT}")
        print("=" * 50)

        buffer = b""

        # Tối đa 3 lần đăng nhập
        for attempt in range(1, 4):

            print(f"\nLần thử {attempt}/3")

            username = input("Nhập tên đăng nhập: ")

            message = f"LOGIN|{username}\n"

            sock.sendall(message.encode("utf-8"))

            response, buffer = recv_line(sock, buffer)

            if response is None:
                print("Server đã đóng kết nối.")
                return

            print(f"Server: {response}")

            # Đăng nhập thành công
            if response.startswith("OK|"):
                print("\nĐăng nhập thành công!")

                while True:
                    command = input(
                        "Nhập QUIT để thoát: "
                    )

                    if command == "QUIT":
                        sock.sendall(b"QUIT\n")
                        print("Đã gửi QUIT.")
                        return

                    print("Chỉ hỗ trợ QUIT.")

            # Đăng nhập thất bại
            else:
                if attempt < 3:
                    print("Đăng nhập thất bại, hãy thử lại.")
                else:
                    print(
                        "Đã thử quá 3 lần. Client thoát."
                    )

    except ConnectionRefusedError:
        print("Không thể kết nối đến server.")

    finally:
        sock.close()


if __name__ == "__main__":
    main()